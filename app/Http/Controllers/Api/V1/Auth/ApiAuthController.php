<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Alonti\Auth\Cake\CakeHasher;
use App\Alonti\Cart\CartManager;
use App\Alonti\ZipManager\ZipManager;
use App\Events\ForgotPasswordRequestedEvent;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Resources\Api\V1\AuthLoginResource;
use App\Http\Resources\Api\V1\ForgotPasswordResource;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ApiAuthController extends Controller
{
    public function __construct(private readonly CartManager $cartManager, private readonly ZipManager $zipManager)
    {
        parent::__construct();
    }

    /**
     * POST /api/v1/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $userRecord = User::where('email', $validated['email'])->first();
        if ($userRecord && $userRecord->account_status == 'Deleted') {
            $cafeManagerEmail = str_ireplace('@alonti.com', '', $userRecord->cafe->csmUser->email) . '@alonti.com';

            return $this->errorResponse(
                'Your account was deactivated. Please contact your catering sales manager ' .
                    $userRecord->cafe->csmUser->name .
                    ' at ' .
                    $cafeManagerEmail .
                    ' to reactivate your account.',
                403
            );
        }

        // Must be before attempting login (same reason as legacy controller).
        $guestUserCart = $this->cartManager->getActiveCart();

        $success = Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']], true);
        /** @var User|null $user */
        $user = Auth::user();

        if (!$success || !$user) {
            return $this->errorResponse('Login attempt failure', 401);
        }

        $lastDeliveredZipcode = app(Order::class)->getLastDeliveryZipcode($user->id);
        if ($lastDeliveredZipcode) {
            $this->zipManager->setDeliveryAreaByZip($lastDeliveredZipcode->zipcode);
        } else {
            $this->zipManager->setDeliveryAreaByZip($user->zip);
        }

        $msg = '';
        if ($user->active_cart_id) {
            $existingActiveCart = Cart::find($user->active_cart_id);
            if ($existingActiveCart && $existingActiveCart->order_id) {
                $existingActiveCart->status = 0;
                $existingActiveCart->save();
                $user->active_cart_id = null;
                $user->save();
                if (!in_array($existingActiveCart->order->status, ['Delivered', 'Canceled'])) {
                    $msg =
                        'Your completed order #' .
                        $existingActiveCart->order_id .
                        ' was edited by you and not updated. Please verify that was updated or not.';
                }
            }
        }

        if ($guestUserCart) {
            $pendingIndividualCart = Cart::where([
                'user_id' => $user->id,
                'order_id' => null,
                'group_order_id' => null,
            ])
                ->where('id', '!=', $guestUserCart->id)
                ->orderBy('id', 'desc')
                ->get();
            if ($pendingIndividualCart->count() > 0) {
                $pendingIndividualCart->each(function (Cart $cart) {
                    $cart->discardCart();
                });
                if (!empty($msg)) {
                    $msg .= ' and your existing individual carts also discarded';
                } else {
                    $msg = 'Your existing individual carts are discarded';
                }
            }
            $guestUserCart->session_id = null;
            $guestUserCart->user_id = $user->id;
            $guestUserCart->save();
            $user->active_cart_id = $guestUserCart->id;
            $user->save();
        }

        $token = $user->createToken('auth-token')->plainTextToken;
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'fname' => $user->fname ?? null,
            'lname' => $user->lname ?? null,
            'phone' => $user->phone ?? null,
        ];

        return $this->successResponse(
            AuthLoginResource::make([
                'token' => $token,
                'user' => $userData,
            ]),
            $msg ?: 'Success',
            200
        );
    }

    /**
     * POST /api/v1/auth/register
     *
     * Reuses the existing full registration behavior.
     */
    public function register(CakeHasher $hasher): JsonResponse
    {
        try {
            return app(LoginController::class)->createRegistration($hasher);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('API registration endpoint failed', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            return $this->serverErrorResponse('Registration failed');
        }
    }

    /**
     * POST /api/v1/auth/forgot-password
     *
     * Reuses the existing forgot password behavior.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        try {
            $email = (string) $request->input('email');
            $user = User::where('email', $email)->first();

            if ($user) {
                $token = Str::random(48);
                $user->update([
                    'forgot_password_link' => $token,
                    'forgot_password_link_valid' => 1,
                ]);

                $hash = $token . '_' . $user->id;
                ForgotPasswordRequestedEvent::dispatch($user, $hash);

                return $this->successResponse(
                    ForgotPasswordResource::make(['account' => true]),
                    'Please follow your mail to reset your password',
                    200
                );
            }

            return $this->successResponse(
                null,
                'This Email ID is not registered. Kindly enter your Alonti login ID to receive your password.',
                200
            );
        } catch (\Throwable $e) {
            Log::error('Forgot password failed', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            return $this->serverErrorResponse('Forgot password failed');
        }
    }

    /**
     * POST /api/v1/auth/reset-password (stateless)
     *
     * Accepts the same hash format used by the legacy reset route: {forgot_password_link}_{user_id}.
     */
    public function resetPassword(Request $request, CakeHasher $hasher): JsonResponse
    {
        $validated = $request->validate([
            'hash' => ['required', 'string'],
            'pass' => ['required', 'string', 'min:6'],
        ]);

        $hash = (string) $validated['hash'];
        $pos = strrpos($hash, '_');
        if ($pos === false) {
            return $this->errorResponse('Not a valid password link', 400);
        }

        $forgotLink = substr($hash, 0, $pos);
        $userId = substr($hash, $pos + 1);
        if ($forgotLink === '' || $userId === '' || !ctype_digit($userId)) {
            return $this->errorResponse('Not a valid password link', 400);
        }

        /** @var User|null $user */
        $user = User::select('id', 'forgot_password_link', 'forgot_password_link_valid')->find((int) $userId);

        if (
            !$user ||
            !$user->forgot_password_link_valid ||
            !$user->forgot_password_link ||
            $user->forgot_password_link !== $forgotLink
        ) {
            return $this->errorResponse('Not a valid password link', 400);
        }

        $hashedPassword = $hasher->make((string) $validated['pass']);
        $user->update([
            'password' => $hashedPassword,
            'forgot_password_link' => '',
            'forgot_password_link_valid' => 0,
        ]);

        return $this->successResponse(null, 'Your password has been reset successfully.', 200);
    }
}
