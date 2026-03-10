<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Alonti\Cart\CartManager;
use App\Alonti\ZipManager\ZipManager;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Zipcode;
use DateTime;
use Illuminate\Http\Request;

class ZipcodeController extends Controller
{
    /**
     * search
     *
     * @param  mixed  $request
     * @param  mixed  $zipManager
     * @return void
     */
    public function search(Request $request, ZipManager $zipManager)
    {
        $data = [];
        $data['status'] = false;
        $data['redirect'] = '';
        $data['msg'] = 'Please enter your zipcode';
        $data['state_id'] = '';
        $data['count'] = 0;

        if ($request->has('zipcode')) {
            $code = $request->get('zipcode');
            $isCafeAvailable = $zipManager->setDeliveryAreaByZip($code);

            if ($isCafeAvailable) {
                $data['status'] = true;

                if ($isCafeAvailable->count() == 1) {
                    $cartInfo = app(CartManager::class)->getActiveCart();
                    $updateCart =
                        !$cartInfo ||
                        ($cartInfo && $cartInfo->order && in_array($cartInfo->order->status, ['Delivered', 'Canceled']))
                            ? false
                            : true;

                    if ($updateCart) {
                        $deliveryInfo = session()->get('UserDeliveryInformation.alontiDeliveryArea');
                        $cartInfo->updateDeliveryArea(
                            $deliveryInfo->zipcode,
                            $deliveryInfo->state_id,
                            $deliveryInfo->cafe->id
                        );
                        $data['state_id'] = $deliveryInfo->state_id;
                    }
                }

                // Flash the session data
                session()->flash('showTeam', true);

                $data['msg'] = 'Success';
                $data['redirect'] = 'home';
                $data['count'] = $isCafeAvailable->count();
                $data['cafe_list'] = $isCafeAvailable;
            } else {
                $data['msg'] = 'Entered zipcode lies outside our normal delivery area';
            }
        }

        return response()->json($data);
    }

    /**
     * setDeliveryAreaByZipId
     *
     * @param  mixed  $request
     * @param  mixed  $zipManager
     * @return void
     */
    public function setDeliveryAreaByZipId(Request $request, ZipManager $zipManager)
    {
        $data = [];
        $data['status'] = false;

        if ($request->has('zipid')) {
            $zipid = $request->get('zipid');
            $zipcodeRecord = $zipManager->findCafeWithId($zipid);
            $cartInfo = app(CartManager::class)->getActiveCart();

            if ($cartInfo) {
                $cartInfo->updateDeliveryArea(
                    $zipcodeRecord->zipcode,
                    $zipcodeRecord->state_id,
                    $zipcodeRecord->cafe->id
                );
            }
            $data['status'] = true;
        }

        return response()->json($data);
    }

    /**
     * getdeliveryinfo
     *
     * @return void
     */
    public function getdeliveryinfo()
    {
        $data = [];
        $data['status'] = false;
        $data['msg'] = 'Failure';
        $data['data'] = [];

        if (session()->has('UserDeliveryInformation.alontiDeliveryArea')) {
            $data['status'] = true;
            $data['msg'] = 'Success';
            $data['data'] = session()->get('UserDeliveryInformation');
        }

        return response()->json($data);
    }

    /**
     * retreiveCateringMgrInfo
     *
     * @return void
     */
    public function retreiveCateringMgrInfo()
    {
        $data = [];
        $data['status'] = false;
        $data['msg'] = 'Failure';
        $data['data'] = [];

        if (session()->has('UserDeliveryInformation.alontiDeliveryArea')) {
            $data['status'] = true;
            $data['msg'] = 'Success';
            $deliveryInfo = session()->get('UserDeliveryInformation.alontiDeliveryArea');

            if (!empty($deliveryInfo->cafe->cm_id)) {
                $managerInfo = User::where('id', $deliveryInfo->cafe->cm_id)->first();
            } else {
                $cafeEmail = $deliveryInfo->cafe->support
                    ? $deliveryInfo->cafe->support->mgremail
                    : $deliveryInfo->cafe->mgremail;
                $cafeEmail = str_replace('@alonti.com', '', $cafeEmail);
                $managerName = explode(' ', $deliveryInfo->cafe->manager);
                $managerFname = $managerName[0];
                $managerLname = $managerName[1];

                $managerInfo = User::where(function ($q) use ($managerFname, $managerLname) {
                    $q->where('fname', $managerFname)->where('lname', $managerLname);
                })
                    ->where('email', '<>', '')
                    ->where('profile_image', '<>', '')
                    ->first();

                if (!$managerInfo) {
                    $managerInfo = User::where('email', 'like', $cafeEmail . '%')->first();
                }
            }

            $deliveryInfo['cafe']['profile_image'] =
                $managerInfo && !empty($managerInfo->profile_image)
                    ? config('custom.ADMINURL.' . config('app.env')) .
                        'upload/staff_profile_image/' .
                        $managerInfo->profile_image
                    : '/images/Coming-Soon-thumb.png';

            $director = $deliveryInfo->cafe->director;

            if ($director) {
                $userInfo = User::where(['id' => $director->alonti_user_id])->first();
                $userInfo->email = $director->email;
            } else {
                $userInfo = $deliveryInfo->cafe->csm_usrid
                    ? User::where(['id' => $deliveryInfo->cafe->csm_usrid])->first()
                    : null;
            }

            $data['data']['cafe'] = $deliveryInfo['cafe'];

            $userInfo->profile_image = !empty($userInfo->profile_image)
                ? config('custom.ADMINURL.' . config('app.env')) .
                    'upload/staff_profile_image/' .
                    $userInfo->profile_image
                : '/images/Coming-Soon-thumb.png';

            $data['data']['user'] = $userInfo;

            session()->put('UserDeliveryInformation.helpInfo', $userInfo);
        }

        return response()->json($data);
    }

    /**
     * pickupClosestCafe
     *
     * @param  mixed  $request
     * @param  mixed  $zipManager
     * @return void
     */
    public function pickupClosestCafe(Request $request, ZipManager $zipManager)
    {
        $data = [];
        $data['status'] = false;
        $data['msg'] = 'Failure';

        if ($request->has('zipcode')) {
            $code = $request->get('zipcode');

            if (session()->get('UserDeliveryInformation.pickup.givenZipCode') == $code) {
                $closestCafes = session()->get('UserDeliveryInformation.pickup.cafes');
            } else {
                $closestCafes = $zipManager->findClosestZipcodeHavingCafes($code);
            }

            if ($closestCafes->count() > 0) {
                session()->put('UserDeliveryInformation.pickup.cafes', $closestCafes);
                $data['status'] = true;
                $data['msg'] = 'Success';
                $data['result'] = $closestCafes;

                if ($request->has('pickup')) {
                    session()->put('UserDeliveryInformation.pickup.givenZipCode', $code);
                }
            }
        }

        return response()->json($data);
    }

    /**
     * Validate weekend and night delivery
     *
     * @param  mixed  $request
     * @return void
     */
    public function validateDeliveryDateTime(Request $request)
    {
        $data = [];
        $data['status'] = false;
        $data['message'] = 'Unexpected error occurred. Please try again later.';
        $data['result'] = [];
        $data['reset_date_field'] = false;
        $data['reset_time_field'] = false;
        $data['allow_weekend_orders'] = false;
        $data['allow_night_orders'] = false;
        $data['delivery_times'] = config('custom.delivery_pickup_time');

        $zipCode = Zipcode::where('zipcode', request()['code'])->with('cafe', 'cafe.market')->first();

        $data['allow_weekend_orders'] = $zipCode->cafe->market->allow_weekend_orders == 1 ? true : false;

        if ($zipCode->cafe->market->allow_night_orders == 1) {
            $data['allow_night_orders'] = true;
            $data['delivery_times'] = config('custom.day_night_delivery_pickup_time');
        }

        if (empty(request()['date']) && empty(request()['time'])) {
            $data['status'] = true;

            return response()->json($data);
        }

        $inputDate = request()['date'];
        // Convert the date to yyyy-mm-dd format
        $deliveryDate = DateTime::createFromFormat('m/d/Y', $inputDate)->format('Y-m-d');
        // Check if the delivery date is on weekends
        $isWeekend = date('N', strtotime($deliveryDate)) >= 6; // true

        // 68, 26, 69, 27, 70, 28, 71, 29, 72, 30, 73, 31, 74, 32 is night order time slots
        $nightOrderTimeSlots = [68, 26, 69, 27, 70, 28, 71, 29, 72, 30, 73, 31, 74, 32];
        $deliveryTimeSelected = request()['time'];

        // If weekend date is selected and cafe doesn't allow weekend orders AND
        // If night time is selected and cafe doesn't allow night orders
        if (
            $zipCode->cafe->market->allow_weekend_orders == 0 &&
            $isWeekend &&
            $zipCode->cafe->market->allow_night_orders == 0 &&
            in_array($deliveryTimeSelected, $nightOrderTimeSlots)
        ) {
            $data['message'] =
                'Currently, we do not offer weekend and nighttime delivery services to this location. Kindly choose an alternative date and time within the range of 6:00 AM to 4:30 PM.';
            $data['reset_date_field'] = true;
            $data['reset_time_field'] = true;

            return response()->json($data);
        }
        if (
            $zipCode->cafe->market->allow_weekend_orders == 0 &&
            !$isWeekend &&
            $zipCode->cafe->market->allow_night_orders == 0 &&
            !in_array($deliveryTimeSelected, $nightOrderTimeSlots)
        ) {
            $data['status'] = true;

            return response()->json($data);
        } elseif ($zipCode->cafe->market->allow_weekend_orders == 0 && $isWeekend) {
            $data['message'] =
                "Currently, we don't deliver to this location on weekends. Please select a different date";
            $data['reset_date_field'] = true;

            return response()->json($data);
        } elseif (
            $zipCode->cafe->market->allow_night_orders == 0 &&
            in_array($deliveryTimeSelected, $nightOrderTimeSlots)
        ) {
            $data['message'] =
                "Currently, we don't deliver to this location on nights. Please select a time between 6:00 AM and 4:30 PM.";
            $data['reset_time_field'] = true;

            return response()->json($data);
        } elseif (
            ($zipCode->cafe->market->allow_weekend_orders == 1 && $zipCode->cafe->market->allow_night_orders == 1) ||
            ($zipCode->cafe->market->allow_weekend_orders == 1 && $zipCode->cafe->market->allow_night_orders == 0) ||
            ($zipCode->cafe->market->allow_weekend_orders == 0 && $zipCode->cafe->market->allow_night_orders == 1)
        ) {
            $data['status'] = true;

            return response()->json($data);
        }

        return response()->json($data);
    }
}
