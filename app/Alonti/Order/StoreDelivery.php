<?php

declare(strict_types=1);


namespace App\Alonti\Order;

use App\Alonti\Cart\CartManager;
use App\Alonti\User\UserManager;
use App\Alonti\ZipManager\ZipManager;
use App\Models\Cafe;
use App\Models\Cim;
use App\Models\Industry;
use App\Models\MxProspect;
use App\Models\Order;
use App\Models\Shipping;
use App\Models\State;
use App\Models\User;
use App\Models\Zipcode;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class StoreDelivery
{
    public $data;

    public $zipManager;

    public $cafe;

    public $cart;

    public $userManager;

    public $user;

    public $client;

    public function __construct($data)
    {
        $this->data = collect($data);
        $this->zipManager = app(ZipManager::class);
        $this->cafe = $this->zipManager->getAlontiDeliveryArea()->cafe;
        $this->cart = app(CartManager::class)->getActiveCart();
        $this->userManager = app(UserManager::class);
        $this->user = Auth::user();
        $this->client = new \GuzzleHttp\Client();
    }

    public function guestValidate()
    {
        if ($this->data->get('isGuestUser')) {
            if (!$this->validateGuestUserInformation()) {
                return false;
            }

            return true;
        }

        return true;
    }

    public function deliveryAreaValidation()
    {
        if ($this->data->get('isPickupSelected') && $this->getPickupDetails()['pickupOptions'] != '') {
            $zipCode = $this->getZipcode();

            if (strlen($zipCode) > 4) {
                return true;
            } else {
                return false;
            }

            return true;
        } else {
            $zipCode = $this->getZipcode();

            if (strlen($zipCode) > 4) {
                return true;
            }

            if ($this->validZipcode($zipCode)) {
                return true;
            }

            $state = $this->getState();

            if ($this->validState($state)) {
                return true;
            }
        }

        return false;
    }

    public function store()
    {
        if ($this->data->get('isGuestUser')) {
            $this->user = User::where('email', $this->getUserDetails()['email'])->first();
            if (!$this->user) {
                $this->createAnAccount();
            }
        }

        $this->cart->order_name = $this->data->get('orderDetails')['orderName'];
        $this->cart->user_id = $this->user->id;
        $this->cart->personalized_message = $this->data->get('orderDetails')['personalisedMsg']
            ? $this->data->get('orderDetails')['personalisedMsg']
            : '';
        $this->cart->zipcode = $this->getZipcode();
        $this->cart->state_id = $this->getState();
        $this->cart->save();
        $this->createShipping();
        $this->zipManager->setDeliveryAreaByZip($this->getZipcode());
        $this->cart->updateDeliveryArea($this->getZipcode(), $this->getState(), $this->getCafeId());

        if ($this->user) {
            if ($this->cart->abandonedCart && !$this->cart->abandonedCart->alonti_user_id) {
                $this->cart->abandonedCart->alonti_user_id = $this->user->id;
                $this->cart->abandonedCart->update();
            }
        }

        return $this->user;
    }

    private function createShipping()
    {
        $industry =
            !empty($this->user->industry_id) && is_numeric($this->user->industry_id)
                ? $this->findIndustry($this->user->industry_id)
                : null;
        $shipping = [
            'cart_id' => $this->cart->id,
            'first_name' => $this->user->fname,
            'last_name' => $this->user->lname,
            'email' => $this->user->email,
            'phone_number' => $this->user->phone,
            'secondary_phone_number' => isset($this->getUserDetails()['secondaryPhone'])
                ? $this->getUserDetails()['secondaryPhone']
                : null,
            'company_id' => $this->user->company_user_id,
            'industry_id' => $industry ? $industry->id : null,
            'address_id' => $this->data->get('pastDeliveryAddress') ? $this->data->get('pastDeliveryAddress') : null,
            'address1' => $this->data->get('isDeliverySelected') ? $this->getAddr() : null,
            'address2' => $this->data->get('isDeliverySelected') ? $this->getAddrTwo() : null,
            'city' => $this->data->get('isDeliverySelected') ? $this->getCity() : null,
            'state' => $this->data->get('isDeliverySelected') ? $this->getState() : null,
            'zipcode' => $this->getZipcode(),
            'shipping_type' => $this->data->get('isDeliverySelected') ? 1 : 2,
            'cafe_id' => $this->getCafeId(),
            'delivery_date' => $this->data->get('isDeliverySelected')
                ? $this->getDeliveryDetails()['deliveryDateValue']
                : $this->getPickupDetails()['pickupDateValue'],
            'delivery_time' => $this->data->get('isDeliverySelected')
                ? $this->getDeliveryDetails()['deliveryTimeSelected']
                : $this->getPickupDetails()['pickupTimeSelected'],
            'delivery_instruction' => $this->data->get('isDeliverySelected')
                ? $this->getDeliveryDetails()['deliveryInstructions']
                : '',
            'number_of_members' => $this->data->get('headCount') != '' ? $this->data->get('headCount') : null,
            'paper_products' => $this->data->get('paper_products') ? 1 : 0,
            'contactless_delivery' => $this->data->get('contactless_delivery') ? 1 : 0,
            'receiver_name' => $this->getOrderDetails()['receiverName'] != '' ? $this->getOrderDetails()['receiverName'] : null,
            'receiver_phone' => $this->getOrderDetails()['receiverPhone'] != '' ? $this->getOrderDetails()['receiverPhone'] : null,
        ];

        if ($this->data->get('isDeliverySelected')) {
            session()->forget('UserDeliveryInformation.pickup');
        }

        if (!$this->cart->shipping) {
            Shipping::create($shipping);
        } else {
            $this->cart->shipping->update($shipping);
        }
    }

    public function findIndustry($industryId)
    {
        return Industry::find($industryId);
    }

    public function createAnAccount()
    {
        $delivery = $this->getDeliveryDetails();
        $guestDetails = $this->getUserDetails();
        $pickupDetails = $this->getPickupDetails();
        $companyUser = $this->userManager->getCompanyUser($this->getCompany(), $this->getCafeId());
        $prospect = $this->userManager->isEmailExistInProspect($guestDetails['email']);
        $stateVal = State::pluck('name', 'id');
        $selectedState = null;

        if ($this->data->get('isDeliverySelected')) {
            $selectedState = $this->getState();

            if (is_numeric($selectedState)) {
                $selectedState = $stateVal[$selectedState];
            }
        }

        if ($this->data->get('isDeliverySelected')) {
            $address = $this->getAddr() . ' ' . $this->getCity() . ' ' . $selectedState . ' ' . $this->getZipcode();
        } else {
            $address = $this->getZipcode();
        }

        $APIKey = env('GoogleMap');
        $authUrl =
            'https://maps.googleapis.com/maps/api/geocode/json?key=' . $APIKey . '&address=' . urlencode($address);
        $response = $this->client->get($authUrl);
        $latLong = json_decode($response->getBody(true)->getContents());

        $registeredUser = User::create([
            'fname' => $guestDetails['firstName'],
            'lname' => $guestDetails['lastname'],
            'email' => $guestDetails['email'],
            'phone' => $guestDetails['phone'],
            'company' => $this->getCompany(),
            'industry_id' => $this->getIndustry(),
            'hsacct' => 0,
            'group_id' => 5,
            'physical_addr' => $this->data->get('isDeliverySelected') ? $this->getAddr() : null,
            'physical_addr2' => $this->data->get('isDeliverySelected') ? $this->getAddrTwo() : null,
            'physical_state' => $selectedState,
            'physical_city' => $this->data->get('isDeliverySelected') ? $this->getCity() : null,
            'physical_zip' => $this->getZipcode(),
            'profile_image' => '',
            'unsubscribe_promotion' => '0',
            'cafe_id' => $this->getCafeId(),
            'company_user_id' => empty($companyUser) ? null : $companyUser->id,
            'type' => 1,
            'addr' => $this->data->get('isDeliverySelected') ? $this->getAddr() : null,
            'city' => $this->data->get('isDeliverySelected') ? $this->getCity() : null,
            'state' => $selectedState,
            'addr2' => $this->data->get('isDeliverySelected') ? $this->getAddrTwo() : null,
            'zip' => $this->getZipcode(),
            'user_source' => $prospect ? 'mx_group' : 'alonti',
            'user_source_id' => $prospect ? $prospect->id : null,
            'latitude' => isset($latLong->results[0]->geometry->location)
                ? $latLong->results[0]->geometry->location->lat
                : null,
            'longitude' => isset($latLong->results[0]->geometry->location)
                ? $latLong->results[0]->geometry->location->lng
                : null,
        ]);

        // Set payment_profile_id=null and update user_id=$registeredUser->id
        if (
            isset($registeredUser->id) &&
            isset($this->cart) &&
            isset($this->cart->id) &&
            $registeredUser->id != $this->cart->user_id
        ) {
            Cim::where('alonti_user_id', $this->cart->user_id)->update([
                'alonti_user_id' => $registeredUser->id,
                'email' => $registeredUser->email,
            ]);
        }

        $this->user = $registeredUser;
    }

    public function getPickupDetails()
    {
        $details = $this->data->get('pickupOption');
        $details['pickupDateValue'] = $details['pickupDateValue']
            ? Carbon::createFromFormat('m/d/Y', $details['pickupDateValue'])->format('Y-m-d')
            : $details['pickupDateValue'];

        return $details;
    }

    public function getDeliveryDetails()
    {
        $details = $this->data->get('deliveryOption');
        $details['deliveryDateValue'] = $details['deliveryDateValue']
            ? Carbon::createFromFormat('m/d/Y', $details['deliveryDateValue'])->format('Y-m-d')
            : $details['deliveryDateValue'];

        return $details;
    }

    public function getUserDetails()
    {
        return $this->data->get('isGuestUser') ? $this->data->get('guestDetails') : $this->data->get('userDetails');
    }

    public function getOrderDetails()
    {
        return $this->data->get('orderDetails');
    }

    public function getCompany()
    {
        return $this->getOrderDetails()['company'];
    }

    public function getIndustry()
    {
        return $this->getOrderDetails()['industrySelected'];
    }

    public function getAddr()
    {
        $address = null;

        if ($this->data->get('isDeliverySelected') && $this->data->get('isDeliverySelectedFromRecent')) {
            $pastDeliveryAddress = $this->fetchDeliveredAddress();
            $address = $pastDeliveryAddress->d_addr;
        } elseif ($this->data->get('isDeliverySelected')) {
            $address = $this->getDeliveryDetails()['address'];
        }

        return $address;
    }

    public function getAddrTwo()
    {
        $address = null;
        if ($this->data->get('isDeliverySelected') && $this->data->get('isDeliverySelectedFromRecent')) {
            $pastDeliveryAddress = $this->fetchDeliveredAddress();
            $address = $pastDeliveryAddress->second_address;
        } elseif ($this->data->get('isDeliverySelected')) {
            $address = $this->getDeliveryDetails()['address_two'];
        }

        return $address;
    }

    public function getCity()
    {
        $city = null;

        if ($this->data->get('isDeliverySelected') && $this->data->get('isDeliverySelectedFromRecent')) {
            $pastDeliveryAddress = $this->fetchDeliveredAddress();
            $city = $pastDeliveryAddress->deliveryCity;
        } elseif ($this->data->get('isDeliverySelected')) {
            $city = $this->getDeliveryDetails()['city'];
        }

        return $city;
    }

    public function getZipcode()
    {
        if ($this->data->get('isDeliverySelected') && $this->data->get('isDeliverySelectedFromRecent')) {
            $pastDeliveryAddress = $this->fetchDeliveredAddress();
            $zipcode = $pastDeliveryAddress->zipcode;
        } elseif ($this->data->get('isPickupSelected')) {
            $zipcode = $this->getPickupDetails()['updatedZipcode'];
        } else {
            $zipcode = $this->getDeliveryDetails()['zipcode'];
        }

        $zip = explode('-', $zipcode);

        if (count($zip) > 1) {
            $zipcode = $zip[0];
        }

        return $zipcode;
    }

    public function getState()
    {
        $cafeId = $this->getCafeId();
        $givenZipcode = $this->getZipcode();
        $cafe = Cafe::where(['id' => $cafeId])
            ->select(['cafenum', 'csz'])
            ->first();
        $zipcode = Zipcode::where(['cafe_id' => $cafe->cafenum, 'zipcode' => $givenZipcode])
            ->with('cafe')
            ->first();

        if ($zipcode) {
            session()->put('UserDeliveryInformation.alontiDeliveryAreaCount', 1);
            session()->put('UserDeliveryInformation.alontiDeliveryArea', $zipcode);
            session()->put('UserDeliveryInformation.givenZipCode', $zipcode->zipcode);
            session()->put('UserDeliveryInformation.deliveryAreaChosen', true);
            $stateId = $zipcode->state_id;
        }

        $state = Zipcode::where(['zipcode' => $givenZipcode])->first();

        return $state->state_id;
    }

    public function getCafeId()
    {
        if ($this->data->get('isPickupSelected')) {
            $cafeId = $this->getPickupDetails()['pickupOptions'];
        } else {
            $cafeId = $this->cafe->id;
        }

        return $cafeId;
    }

    public function fetchDeliveredAddress()
    {
        // Need to store the selected recent delivery address in session
        return Order::find($this->data->get('pastDeliveryAddress'));
    }

    public function isEmailExistInProspect()
    {
        return MxProspect::where('email', 'like', "%{$this->getUserDetails()['email']}%")->first();
    }

    public function validateGuestUserInformation()
    {
        $guestDetails = $this->getUserDetails();
        $firstName = $this->validName($guestDetails['firstName']);
        $lastName = $this->validName($guestDetails['lastname']);
        $phone = $this->validName($guestDetails['phone']);
        $email = $this->validName($guestDetails['email']);

        if ($firstName && $lastName && $phone && $email) {
            return true;
        }

        return false;
    }

    public function validName($name)
    {
        return empty($name) ? false : true;
    }

    public function validPhone($number)
    {
        // Allow +, - and . in phone number
        $filtered_phone_number = filter_var($number, FILTER_SANITIZE_NUMBER_INT);
        // Remove "-" from number
        $phone_to_check = str_replace('-', '', $filtered_phone_number);
        // Check the lenght of number
        // This can be customized if you want phone number from a specific country

        if (strlen($phone_to_check) < 10 || strlen($phone_to_check) > 14) {
            return false;
        } else {
            return true;
        }
    }

    public function validEmail($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        return true;
    }

    public function validZipcode($zipCode)
    {
        if (empty($zipCode)) {
            return false;
        }
        $result = $this->zipManager->findClosestZipcodeHavingCafe($zipCode);

        return !$result ? false : true;
    }

    public function validState($state)
    {
        if (empty($state)) {
            return false;
        }

        $result = State::where(['status' => 1, 'id' => $state])->first();

        return $result->count() == 0 ? false : true;
    }
}
