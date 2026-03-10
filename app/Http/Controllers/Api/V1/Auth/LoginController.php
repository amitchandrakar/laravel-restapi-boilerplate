<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Alonti\Auth\Cake\CakeHasher;
use App\Alonti\Cart\CartManager;
use App\Alonti\User\UserManager;
use App\Alonti\ZipManager\ZipManager;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Configuration;
use App\Models\CustomerReferral;
use App\Models\Industry;
use App\Models\Order;
use App\Models\State;
use App\Models\User;
use App\Models\UserConfiguration;
use App\Traits\SocialAuthSettings;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class LoginController extends Controller
{
    use AuthenticatesUsers, SocialAuthSettings;

    protected $redirectTo = 'hello';

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

                return redirect($redirect)->with('errorMessage', session('errorMessage'));
            }

            return redirect($redirect);
        }

        $cartInfo = $this->cartManager->getActiveCart();
        $redirectDeliveryDetailPage = $cartInfo && $cartInfo->items->isNotEmpty();
        $socialLoginSettings = DB::select('select * from settings')[0];

        return view('login.login', compact('redirectDeliveryDetailPage', 'socialLoginSettings'));
    }

    public function verifyLogin(CartManager $cartManager)
    {
        /* Disabling google recaptcha because of an issue
        // Google Recaptcha request
        $gRecaptchaResponse = request()->g_recaptcha;

        if (is_null($gRecaptchaResponse) ) {
            // If $gRecaptchaResponse is null means there could be any error with "site url" on google recaptcha admin console or with google recaptcha key)
            return $this->googleRecaptchaErrorMessage();
        }

        // Validate Google Recaptcha
        $validateRecaptcha = $this->validateGoogleRecaptcha($gRecaptchaResponse);

        if (!$validateRecaptcha) {
            // If $validateRecaptcha is null means there could be any error with google recaptcha secret or with code)
            return $this->googleRecaptchaErrorMessage();
        }
        */

        $credentials = request()->only('email', 'password');
        $data = User::where('email', $credentials['email'])->first();
        if ($data && $data->account_status == 'Deleted') {
            $cafeManagerEmail = str_ireplace('@alonti.com', '', $data->cafe->csmUser->email) . '@alonti.com';

            return response([
                'status' => false,
                'success' => 'Your account was deactivated. Please contact your catering sales manager ' .
                    $data->cafe->csmUser->name .
                    ' at ' .
                    $cafeManagerEmail .
                    ' to reactivate your account.',
            ]);
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

        $url = session()->get('url.backto', '/');

        if ($msg) {
            return response(['status' => true, 'exception' => true, 'success' => $msg, 'redirectTo' => $url]);
        }

        return response(['status' => true, 'exception' => false, 'success' => $success, 'redirectTo' => $url]);
    }

    /**
     * Show Google Recaptcha error message
     *
     * @return array of status and message
     */
    public function googleRecaptchaErrorMessage()
    {
        return response(['status' => false, 'success' => 'Google Recaptcha verification failed. Please try again.']);
    }

    /**
     * Verify Google Recaptcha to send secret and response to site verify url - https://www.google.com/recaptcha/api/siteverify
     *
     * @param  varchar  $googleRecaptchaResponse  value coming from login page
     * @return success response or null
     */
    public function validateGoogleRecaptcha($googleRecaptchaResponse)
    {
        $client = new Client();

        $googleRecaptchaResponse = is_null($googleRecaptchaResponse) ? '' : $googleRecaptchaResponse;

        $secret = config('app.google_recaptcha_secret');

        $response = $client->post('https://www.google.com/recaptcha/api/siteverify', [
            'form_params' => [
                'secret' => $secret,
                'response' => $googleRecaptchaResponse,
            ],
        ]);
        $body = json_decode((string) $response->getBody());

        return $body->success;
    }

    public function guestCheckout()
    {
        session()->put('via-guest-checkout', true);

        return redirect(session()->get('url.backto', '/'));
    }

    public function logout()
    {
        Auth::logout();
        session()->remove('via-guest-checkout');
        session()->remove('url');

        return redirect('/')->with('notify-success', 'You have been successfully logged out.');
    }

    public function forgotPassword()
    {
        $email = request('email');
        $user = User::where('email', $email)->first();

        if ($user) {
            $user->mailer()->sendForgotPasswordEmail();

            return [
                'success' => true,
                'account' => true,
                'message' => 'Please follow your mail to reset your password',
            ];
        }

        return [
            'success' => true,
            'message' => 'This Email ID is not registered. Kindly enter your Alonti login ID to receive your password.',
        ];
    }

    public function resetPassword(string $hash)
    {
        [$forgot_password_link, $user_id] = explode('_', $hash);

        if (!$user_id || !$forgot_password_link) {
            return 'Not a valid password link';
        }

        $user = User::select('id', 'forgot_password_link', 'forgot_password_link_valid')->find($user_id);

        if (!$user || !$user->forgot_password_link_valid) {
            return 'Not a valid password link';
        }
        session()->put('user.reset_password_id', $user->id);

        return view('login.reset-password');
    }

    public function saveResetPassword(CakeHasher $hasher)
    {
        $password = request('pass');

        $user_id = session()->get('user.reset_password_id');
        if (!$user_id) {
            return ['success' => false, 'message' => 'Reset password session has expired.'];
        }
        $hashed_password = $hasher->make($password);

        $user = User::find($user_id);
        $user->update([
            'password' => $hashed_password,
            'forgot_password_link' => '',
            'forgot_password_link_valid' => 0,
        ]);
        session()->forget('user.reset_password_id');

        return ['success' => true, 'message' => 'Your password has been reset successfully.'];
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

        return view(
            'login.register',
            compact(
                'industries',
                'states',
                'displayRewardGreet',
                'displayReferralRewardGreet',
                'referralConfig',
                'toEmail'
            )
        );
    }

    public function createRegistration(CakeHasher $hasher)
    {
        $registrationData = request()->all();
        $hashed_password = $hasher->make($registrationData['password']);
        DB::beginTransaction();
        $userRecord = User::where(['email' => $registrationData['email'], 'group_id' => 5])->first();
        if ($userRecord && !$userRecord->type) {
            return [
                'success' => false,
                'message' => 'The given email already exists. Please select another one.',
            ];
        }
        $storedFile = null;
        if (request()->has('file') && request('file')) {
            $storedFile = Storage::disk(config('custom.tax_exempt_document_disk'))->put(
                'tax-exempt-documents',
                request('file')
            );
        }

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
        $state = State::where('id', $registrationData['stateSelected'])->first();
        $data = [
            'fname' => $registrationData['firstName'],
            'lname' => $registrationData['lastname'],
            'password' => $hashed_password,
            'email' => $registrationData['email'],
            'secondary_email' => $registrationData['secondaryemail'],
            'phone' => $registrationData['phone'],
            'secondary_phone' => isset($registrationData['secondaryphone'])
                ? $registrationData['secondaryphone']
                : null,
            'company' => $registrationData['company'],
            'industry_id' => $registrationData['industrySelected'],
            'hsacct' => 0,
            'group_id' => 5,
            'txexempt' => 0,
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
            'unsubscribe_promotion' => $registrationData['subscribe'] ? '' : 'UNS',
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
            $userRecord->mailer()->sendWelcomeEmail();
        } else {
            $registeredUser = User::create($data);
            $registeredUser->mailer()->sendWelcomeEmail();
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

        return ['success' => true, 'message' => 'User has been registered successfully.', 'redirectTo' => $url];
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

                return redirect('/')->with(
                    'notify-failure',
                    'Your account was deactivated. Please contact your catering sales manager ' .
                        $user->cafe->csmUser->name .
                        ' at ' .
                        $cafeManagerEmail .
                        ' to reactivate your account.'
                );
            }
            $success = Auth::login($user);
            $authUser = auth()->user();
            if ($authUser) {
                return redirect('/')->with('notify-success', 'Logged in successfully');
            } else {
                return redirect('/')->with('notify-failure', 'Login attempt failure');
            }
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
                    return redirect($urlFailed)->with('error', 'You have cancelled the login process.');
                } else {
                    // Log or handle other cases as needed
                    Log::error('LinkedIn OAuth Error: ' . $e->getMessage());

                    return redirect($urlFailed)->with(['errorMessage' => 'An error occurred. Please try again later.']);
                }
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

                    return redirect($urlFailed)->with(['errorMessage' => $errorMessage]);
                }
                // Handle other exceptions
                Log::error('Request Exception: ' . $e->getMessage());

                return redirect($urlFailed)->with(['errorMessage' => 'An error occurred. Please try again later.']);
            } catch (Exception $e) {
                return redirect($urlFailed)->with(['errorMessage' => $e->getMessage()]);
            }

            if (!$data->email) {
                return redirect($urlFailed)->with([
                    'errorMessage' => 'Not able to access your email at this time. Please try again later.',
                ]);
            }

            $user = User::where(['email' => $data->email])->first();

            if (!$user) {
                $user = $this->createNewUser($data);
                Auth::login($user, true);

                return redirect($urlSuccess);
            }

            if ($user->account_status === 'Deleted') {
                // dd('Account deleted');
                $cafeManagerEmail = str_ireplace('@alonti.com', '', $user->cafe->csmUser->email) . '@alonti.com';

                return redirect($urlFailed)->with([
                    'errorMessage' => 'Your account was deactivated. Please contact your catering sales manager ' .
                        $user->cafe->csmUser->name .
                        ' at ' .
                        $cafeManagerEmail .
                        ' to reactivate your account.',
                ]);
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

            return redirect($urlSuccess);
        } catch (Exception $e) {
            return redirect($urlFailed)->with(['errorMessage' => $e->getMessage()]);
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
