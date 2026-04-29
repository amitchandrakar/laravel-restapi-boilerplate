<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Alonti\Auth\Cake\CakeHasher;
use App\Alonti\Cart\CartManager;
use App\Alonti\User\UserManager;
use App\Alonti\ZipManager\ZipManager;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Resources\Api\V1\AuthLoginResource;
use App\Http\Resources\Api\V1\ForgotPasswordResource;
use App\Http\Resources\Api\V1\LoginPageResource;
use App\Http\Resources\Api\V1\RedirectResource;
use App\Http\Resources\Api\V1\RegisterFormResource;
use App\Http\Resources\Api\V1\ResetPasswordResource;
use App\Models\Cart;
use App\Models\Configuration;
use App\Models\CustomerReferral;
use App\Models\Industry;
use App\Models\Order;
use App\Models\State;
use App\Models\User;
use App\Models\UserConfiguration;
use Exception;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class LoginController extends Controller
{
    protected $redirectTo = '/';

    public $cartManager;

    public $userManager;

    public $zipManager;

    public $client;

    public function __construct()
    {
        // Call the parent constructor
        parent::__construct();
        $this->middleware('guest')->except('logout');
        $this->cartManager = app(CartManager::class);
        $this->userManager = app(UserManager::class);
        $this->zipManager = app(ZipManager::class);
        $this->client = new \GuzzleHttp\Client();
    }

    public function username()
    {
        return 'email';
    }

    public function login()
    {
        if (request()->has('backto')) {
            if (stripos(request()->get('backto'), 'reset-password') === false) {
                session()->remove('url.intended');
                session()->put('url.backto', url(request()->get('backto')));
            }
        } else {
            $url = session()->get('url.intended') ?: url()->previous();
            $path = parse_url($url)['path'];

            $urls = config('custom.ignoreRedirectBackUrls');
            $hasAMatch = false;
            foreach ($urls as $url) {
                if (strpos($path, $url) !== false) {
                    $hasAMatch = true;
                    break;
                }
            }

            $redirect = url()->current() . '?backto=' . urlencode($path);

            if ($hasAMatch || session()->has('errorMessage')) {
                $redirect = $hasAMatch ? url()->current() . '?backto=' . urlencode('/') : $redirect;

                return $this->errorResponse(session('errorMessage', 'An error occurred'), 400);
            }

            return $this->successResponse(RedirectResource::make(['redirect' => $redirect]), 'Success');
        }

        $cartInfo = $this->cartManager->getActiveCart();
        $redirectDeliveryDetailPage = $cartInfo && $cartInfo->items->isNotEmpty();
        $socialLoginSettings = DB::select('select * from settings')[0];

        return $this->successResponse(
            LoginPageResource::make([
                'redirect_delivery_detail_page' => $redirectDeliveryDetailPage,
                'social_login_settings' => $socialLoginSettings,
            ]),
            'Success'
        );
    }

    public function verifyLogin(CartManager $cartManager)
    {
        $credentials = request()->only('email', 'password');
        $data = User::where('email', $credentials['email'])->first();
        if ($data && $data->account_status == 'Deleted') {
            $cafeManagerEmail = str_ireplace('@alonti.com', '', $data->cafe->csmUser->email) . '@alonti.com';

            return $this->errorResponse(
                'Your account was deactivated. Please contact your catering sales manager ' .
                    $data->cafe->csmUser->name .
                    ' at ' .
                    $cafeManagerEmail .
                    ' to reactivate your account.',
                403
            );
        }

        // It must be before attempting to loging
        // otherwise get active cart will give you
        // logged in user's cart which will be empty
        $guestUserCart = $cartManager->getActiveCart();
        // $success = Auth::attempt($credentials, request()->remember);;
        $success = Auth::attempt($credentials, true);
        $user = auth()->user();
        if ($user) {
            $lastDeliveredZipcode = app(Order::class)->getLastDeliveryZipcode($user->id);
            if ($lastDeliveredZipcode) {
                $this->zipManager->setDeliveryAreaByZip($lastDeliveredZipcode->zipcode);
            } else {
                $this->zipManager->setDeliveryAreaByZip($user->zip);
            }
        }
        $msg = '';
        if ($success) {
            if ($user && $user->active_cart_id) {
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
                    'user_id' => auth()->user()->id,
                    'order_id' => null,
                    'group_order_id' => null,
                ])
                    ->where('id', '!=', $guestUserCart->id)
                    ->orderBy('id', 'desc')
                    ->get();
                if ($pendingIndividualCart && $pendingIndividualCart->count() > 0) {
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
        }

        if (!$success) {
            return $this->errorResponse('Login attempt failure', 401);
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
            $msg ?: 'Success'
        );
    }

    public function guestCheckout()
    {
        session()->put('via-guest-checkout', true);

        return $this->successResponse(
            RedirectResource::make(['redirect' => session()->get('url.backto', '/')]),
            'Success'
        );
    }

    public function logout()
    {
        Auth::logout();
        session()->remove('via-guest-checkout');
        session()->remove('url');

        return $this->successResponse(null, 'You have been successfully logged out.');
    }

    public function forgotPassword()
    {
        $email = request('email');
        $user = User::where('email', $email)->first();

        if ($user) {
            $token = \Illuminate\Support\Str::random(48);
            $user->update([
                'forgot_password_link' => $token,
                'forgot_password_link_valid' => 1,
            ]);

            $hash = $token . '_' . $user->id;
            \App\Events\ForgotPasswordRequestedEvent::dispatch($user, $hash);

            return $this->successResponse(
                ForgotPasswordResource::make(['account' => true]),
                'Please follow your mail to reset your password'
            );
        }

        return $this->successResponse(
            null,
            'This Email ID is not registered. Kindly enter your Alonti login ID to receive your password.'
        );
    }

    public function resetPassword(string $hash)
    {
        [$forgot_password_link, $user_id] = explode('_', $hash);

        if (!$user_id || !$forgot_password_link) {
            return $this->errorResponse('Not a valid password link', 400);
        }

        $user = User::select('id', 'forgot_password_link', 'forgot_password_link_valid')->find($user_id);

        if (!$user || !$user->forgot_password_link_valid) {
            return $this->errorResponse('Not a valid password link', 400);
        }
        session()->put('user.reset_password_id', $user->id);

        return $this->successResponse(ResetPasswordResource::make(['user_id' => $user->id]), 'Success');
    }

    public function saveResetPassword(CakeHasher $hasher)
    {
        $password = request('pass');

        $user_id = session()->get('user.reset_password_id');
        if (!$user_id) {
            return $this->errorResponse('Reset password session has expired.', 400);
        }
        $hashed_password = $hasher->make($password);

        $user = User::find($user_id);
        $user->update([
            'password' => $hashed_password,
            'forgot_password_link' => '',
            'forgot_password_link_valid' => 0,
        ]);
        session()->forget('user.reset_password_id');

        return $this->successResponse(null, 'Your password has been reset successfully.');
    }

    public function register()
    {
        $displayRewardGreet = false;
        if (request()->has('rewards')) {
            $displayRewardGreet = true;
        }
        $displayReferralRewardGreet = false;
        if (request()->has('referral-rewards')) {
            $displayReferralRewardGreet = true;
        }
        if (request()->has('refer-a-friend')) {
            $displayReferralRewardGreet = true;
        }
        $toEmail = '';
        if (request()->has('email')) {
            $toEmail = request()->get('email');
        }
        $referralConfig = Configuration::where([
            'column_key' => 'referral-reward-value',
            'field_key' => 'referral-range-value',
        ])->first();

        $industries = Industry::all();
        $states = State::all();

        return $this->successResponse(
            RegisterFormResource::make([
                'industries' => $industries,
                'states' => $states,
                'display_reward_greet' => $displayRewardGreet,
                'display_referral_reward_greet' => $displayReferralRewardGreet,
                'referral_config' => $referralConfig,
                'to_email' => $toEmail,
            ]),
            'Success'
        );
    }

    public function createRegistration(CakeHasher $hasher)
    {
        $registrationData = request()->all();
        $isCorporateSignup = filter_var($registrationData['isCorporateSignup'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $isTaxExempt = filter_var($registrationData['isTaxExempt'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $validator = \Illuminate\Support\Facades\Validator::make($registrationData, [
            'email' => ['required', 'email'],
            'password' => ['required'],
            'firstName' => [$isCorporateSignup ? 'nullable' : 'required'],
            'lastname' => [$isCorporateSignup ? 'nullable' : 'required'],
            'phone' => [$isCorporateSignup ? 'nullable' : 'required'],
            'company' => ['required'],
            'industrySelected' => ['required'],
            'address' => ['required'],
            'address_two' => ['required'],
            'city' => ['required'],
            'zipcode' => ['required'],
            'stateSelected' => [$isCorporateSignup ? 'nullable' : 'required'],
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $hashed_password = $hasher->make($registrationData['password']);

        try {
            $userRecord = User::where(['email' => $registrationData['email'], 'group_id' => 5])->first();
            if ($userRecord && !$userRecord->type) {
                return $this->errorResponse('The given email already exists. Please select another one.', 409);
            }

            $storedFile = null;
            $taxExemptForm = request()->file('taxExemptForm') ?: request()->file('file');
            if ($isTaxExempt && !$taxExemptForm) {
                return $this->errorResponse('Tax exempt form is required when isTaxExempt is true.', 422);
            }
            if ($taxExemptForm) {
                $validator = \Illuminate\Support\Facades\Validator::make(
                    ['taxExemptForm' => $taxExemptForm],
                    ['taxExemptForm' => ['file', 'mimetypes:application/pdf']]
                );
                if ($validator->fails()) {
                    return $this->errorResponse('taxExemptForm must be a PDF.', 422);
                }
                $storedFile = Storage::disk(config('custom.tax_exempt_document_disk'))->putFile(
                    'tax-exempt-documents',
                    $taxExemptForm
                );
            }

            DB::beginTransaction();

            $prospect = $this->userManager->isEmailExistInProspect($registrationData['email']);
            $findClosestCafe = $this->zipManager->findClosestZipcodeHavingCafe($registrationData['zipcode']);
            $cafeIdExist = null;
            if ($findClosestCafe && $findClosestCafe->count() > 0) {
                $findClosestCafe = $findClosestCafe[0];
                $cafeIdExist = $findClosestCafe->cafe->id;
            } elseif (session()->has('UserDeliveryInformation.alontiDeliveryArea')) {
                $findClosestCafe = session()->get('UserDeliveryInformation.alontiDeliveryArea');
                $cafeIdExist = $findClosestCafe->cafe->id;
            } else {
                $findClosestCafe = null;
            }
            $companyUser = $this->userManager->getCompanyUser($registrationData['company'], $cafeIdExist);
            // $companyUser = ($findClosestCafe) ? $this->userManager->getCompanyUser($registrationData['company'],$findClosestCafe->cafe->id):null;
            $state = isset($registrationData['stateSelected']) && $registrationData['stateSelected']
                ? State::where('id', $registrationData['stateSelected'])->first()
                : null;
            $data = [
                'fname' => $registrationData['firstName'] ?? '',
                'lname' => $registrationData['lastname'] ?? '',
                'password' => $hashed_password,
                'email' => $registrationData['email'],
                'secondary_email' => $registrationData['secondaryemail'] ?? null,
                'phone' => $registrationData['phone'] ?? '',
                'secondary_phone' => isset($registrationData['secondaryphone'])
                    ? $registrationData['secondaryphone']
                    : null,
                'company' => $registrationData['company'],
                'industry_id' => $registrationData['industrySelected'],
                'hsacct' => 0,
                'group_id' => 5,
                'txexempt' => $isTaxExempt ? 1 : 0,
                'txexempt_file' => $storedFile,
                'physical_addr' => $registrationData['address'],
                'physical_addr2' => $registrationData['address_two'],
                'physical_state' => $state ? $state->name : '',
                'physical_city' => $registrationData['city'],
                'physical_zip' => $registrationData['zipcode'],
                'addr' => $registrationData['address'],
                'city' => $registrationData['city'],
                'state' => $state ? $state->name : '',
                'addr2' => $registrationData['address_two'],
                'zip' => $registrationData['zipcode'],
                'profile_image' => '',
                'unsubscribe_promotion' => ($registrationData['subscribe'] ?? false) ? '' : 'UNS',
                'sms_opt_in' => filter_var($registrationData['sms_opt_in'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'cafe_id' => $cafeIdExist,
                'company_user_id' => !$companyUser ? null : $companyUser->id,
                'user_source' => $prospect ? 'mx_group' : 'alonti',
                'user_source_id' => $prospect ? $prospect->id : null,
                'customermenu_id' => 1,
                'type' => 0,
                'payment_id' => 4,
            ];

        /* Lat Long update */
        $address = $data['addr'] . ' ' . $data['city'] . ' ' . $data['state'] . ' ' . $data['zip'];
        $APIKey = env('GoogleMap');
        $authUrl =
            'https://maps.googleapis.com/maps/api/geocode/json?key=' . $APIKey . '&address=' . urlencode($address);
        $response = $this->client->get($authUrl);
        $latLong = json_decode($response->getBody(true)->getContents());
        if (isset($latLong->results[0]->geometry->location)) {
            $data['latitude'] = $latLong->results[0]->geometry->location->lat;
            $data['longitude'] = $latLong->results[0]->geometry->location->lng;
        }

        if ($userRecord && $userRecord->type) {
            $registeredUser = $userRecord->update($data);
        } else {
            $registeredUser = User::create($data);
        }
        if ($registeredUser) {
            $referralRecord = CustomerReferral::where([
                'email' => $registrationData['email'],
            ])->first();
            if ($referralRecord) {
                $referrerData['registered'] = 1;
                $referralRecord->update($referrerData);
            }
            $rewardConfig = UserConfiguration::where(['user_id' => $registeredUser->id])->first();
            if (!$rewardConfig) {
                $rewardConfigData = [
                    'user_id' => $registeredUser->id,
                    'alonti_rewards' => $registrationData['rewards_checkout'] == true ? 1 : 0,
                    'reward_email' => $registrationData['email'],
                    'created_by' => 0,
                ];
                UserConfiguration::create($rewardConfigData);
            } elseif (
                $rewardConfig &&
                !$rewardConfig->alonti_rewards &&
                $registrationData['rewards_checkout'] == true
            ) {
                $rewardConfig->update([
                    'alonti_rewards' => 1,
                ]);
            }
            if ($cafeIdExist) {
                $registeredUser->mailer()->sendCsmWelcomeEmail();
            }
            if ($registeredUser['txexempt_file']) {
                $registeredUser->mailer()->sendTaxExemptionEmail();
            }
        }
            DB::commit();

            $uri = session('url.backto') ? str_replace(url('/'), '', session('url.backto')) : '/';
            $uri = $uri ?: '/';
            $url = url('/login') . '?backto=' . urlencode($uri);

            return $this->successResponse(
                RedirectResource::make(['redirect' => $url]),
                'User has been registered successfully.'
            );
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            \Illuminate\Support\Facades\Log::error('Registration failed', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            return $this->serverErrorResponse('Registration failed');
        }
    }

    public function adminLogin($encryptedUserId = null)
    {
        if (!empty($encryptedUserId)) {
            // echo $encryptedUserId;
            $encryptedUserId = str_replace('Iamalonti', '/', $encryptedUserId);
            // dd($encryptedUserId);
            $ciphering = config('custom.AdminDecryption.ciphering');
            $decryption_iv = config('custom.AdminDecryption.decryption_iv');
            $decryption_key = config('custom.AdminDecryption.decryption_key');
            $options = 0;
            $decryptId = openssl_decrypt($encryptedUserId, $ciphering, $decryption_key, $options, $decryption_iv);
            $user = User::where(['id' => $decryptId])->first();
            // dd($decryptId, $user);
            if ($user && $user->account_status == 'Deleted') {
                $cafeManagerEmail = str_ireplace('@alonti.com', '', $user->cafe->csmUser->email) . '@alonti.com';

                return $this->errorResponse(
                    'Your account was deactivated. Please contact your catering sales manager ' .
                        $user->cafe->csmUser->name .
                        ' at ' .
                        $cafeManagerEmail .
                        ' to reactivate your account.',
                    403
                );
            }
            $success = Auth::login($user);
            $authUser = auth()->user();
            if ($authUser) {
                return $this->successResponse(null, 'Logged in successfully');
            }

            return $this->errorResponse('Login attempt failure', 401);
        }
    }

    /**
     * redirect
     *
     * @param  mixed  $provider
     * @return void
     */
    public function redirect($provider)
    {
        $this->setSocailAuthConfigs();

        // get page query parameter from url
        $page = request()->get('page');

        // Save the url to session
        session()->put('page', $page);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * callback
     *
     * @param  mixed  $request
     * @param  mixed  $provider
     * @return void
     */
    public function callback(Request $request, $provider)
    {
        $this->setSocailAuthConfigs();
        $urlSuccess = '/summary';
        $urlFailed = '/login';

        // Check for page in session
        if (session()->has('page')) {
            $url = session()->get('page');
            if ($url === 'group-order') {
                $urlSuccess = '/group-order/invite-to-order';
                $urlFailed = '/group-order/login';
            }
            // Remove the page from session
            session()->remove('page');
        }

        try {
            try {
                $data = Socialite::driver($provider)->user();
            } catch (InvalidStateException $e) {
                // Check if the exception message contains "state"
                if (strpos($e->getMessage(), 'state') !== false) {
                    return $this->errorResponse('You have cancelled the login process.', 400);
                }
                // Log or handle other cases as needed
                Log::error('LinkedIn OAuth Error: ' . $e->getMessage());

                return $this->errorResponse('An error occurred. Please try again later.', 500);
            } catch (RequestException $e) {
                if ($e->hasResponse()) {
                    try {
                        $response = $e->getResponse();
                        $errorMessage = json_decode($response->getBody(), true)['error']['message'];
                    } catch (\Throwable $th) {
                        Log::error('Error decoding JSON response: ' . $th->getMessage());
                        $errorMessage = 'An error occurred. Please try again later.';
                    }
                    // Log or handle the error message as needed
                    Log::error('Facebook OAuth Error: ' . $errorMessage);

                    return $this->errorResponse($errorMessage, 500);
                }
                // Handle other exceptions
                Log::error('Request Exception: ' . $e->getMessage());

                return $this->errorResponse('An error occurred. Please try again later.', 500);
            } catch (Exception $e) {
                return $this->errorResponse($e->getMessage(), 500);
            }

            if (!$data->email) {
                return $this->errorResponse('Not able to access your email at this time. Please try again later.', 400);
            }

            $user = User::where(['email' => $data->email])->first();

            if (!$user) {
                $user = $this->createNewUser($data);
                Auth::login($user, true);

                return $this->successResponse(RedirectResource::make(['redirect' => $urlSuccess]), 'Success');
            }

            if ($user->account_status === 'Deleted') {
                $cafeManagerEmail = str_ireplace('@alonti.com', '', $user->cafe->csmUser->email) . '@alonti.com';

                return $this->errorResponse(
                    'Your account was deactivated. Please contact your catering sales manager ' .
                        $user->cafe->csmUser->name .
                        ' at ' .
                        $cafeManagerEmail .
                        ' to reactivate your account.',
                    403
                );
            }

            $cartManager = new CartManager();
            $guestUserCart = $cartManager->getActiveCart();

            Auth::login($user, true); // Login the user

            if (auth()->user()) {
                $user = auth()->user();

                $lastDeliveredZipcode = app(Order::class)->getLastDeliveryZipcode($user->id);

                if ($lastDeliveredZipcode) {
                    $this->zipManager->setDeliveryAreaByZip($lastDeliveredZipcode->zipcode);
                } else {
                    $this->zipManager->setDeliveryAreaByZip($user->zip);
                }

                if ($user && $user->active_cart_id) {
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
                        'user_id' => auth()->user()->id,
                        'order_id' => null,
                        'group_order_id' => null,
                    ])
                        ->where('id', '!=', $guestUserCart->id)
                        ->orderBy('id', 'desc')
                        ->get();
                    if ($pendingIndividualCart && $pendingIndividualCart->count() > 0) {
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
            }

            return $this->successResponse(RedirectResource::make(['redirect' => $urlSuccess]), 'Success');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * createNewUser
     *
     * @param  mixed  $data
     * @return void
     */
    public function createNewUser($data)
    {
        $fullName = $this->extractFirstNameLastName($data->name);
        $firstName = $fullName['first_name'] . ' ' . $fullName['middle_name'];
        $lastName = $fullName['last_name'];

        $user = User::create([
            'fname' => $firstName,
            'lname' => $lastName,
            'email' => $data->email,
            'group_id' => 5,
            'type' => 0,
            'social_login' => 1,
        ]);

        return $user;
    }

    /**
     * extractFirstNameLastName
     *
     * @param  mixed  $fullName
     * @return void
     */
    public function extractFirstNameLastName($fullName)
    {
        // Split the full name into an array of words
        $nameParts = explode(' ', $fullName);

        // Extract the first name
        $firstName = $nameParts[0];

        // Extract the last name
        $lastName = end($nameParts);

        // If the full name contains more than two words, consider the second word as the middle name
        $middleName = count($nameParts) > 2 ? $nameParts[1] : '';

        // If the last name is not set, make the first name the last name
        $lastName = empty($lastName) ? $firstName : $lastName;

        // Return an associative array with first, middle, and last names
        return [
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
        ];
    }
}