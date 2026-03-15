<?php

declare(strict_types=1);

namespace App\Models;

use App\Alonti\Order\ReOrder;
use App\Alonti\Support\EncryptIdentity;
use App\Mailer\OrderMailer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * @property int|null $id
 * @property float|string $total
 * @property int|null $group_order_id
 * @property string|null $status
 * @property string|null $order_name
 * @property string|null $orderName
 * @property \Carbon\Carbon|string|null $d_date
 * @property string|null $encrypted_id
 * @property \App\Models\Time|null $time
 * @property \App\Models\Payment|null $payment
 * @property int|null $is_new_order
 * @property int|null $web
 */
class Order extends BaseModel
{
    use EncryptIdentity;

    protected static $unguarded = true;

    public $timestamps = false;

    protected $dates = ['d_date', 'ordate'];

    const CHECKOUT_TYPE_LOGGED = 0;

    const CHECKOUT_TYPE_GUEST = 1;

    const IS_NEW_ORDER = 1;

    const WEB_CUSTOMER_SITE = 0;

    const WEB_ADMIN_SITE = 1;

    const CUSTOMER_MENU = 1;

    public function scopeExistAddress($query)
    {
        return $query->where([
            'address_status' => 1,
            'pdflag' => 1,
        ]);
    }

    public function getDeliveryAddressAttribute()
    {
        $this->d_addr = str_replace(',,', '', $this->d_addr);
        if (stripos($this->d_addr, $this->second_address) !== false) {
            $this->d_addr = str_ireplace($this->second_address, ',', $this->d_addr);
        }
        if (stripos($this->d_addr, $this->deliveryCity) !== false) {
            $this->d_addr = str_ireplace($this->deliveryCity, ',', $this->d_addr);
        }
        if (stripos($this->d_addr, $this->state) !== false) {
            $this->d_addr = str_ireplace($this->state, ',', $this->d_addr);
        }
        if (stripos($this->d_addr, $this->zipcode) !== false) {
            $this->d_addr = str_ireplace($this->zipcode, ',', $this->d_addr);
        }
        $addr =
            $this->d_addr .
            ', ' .
            $this->second_address .
            ', ' .
            $this->deliveryCity .
            ', ' .
            $this->state .
            ', ' .
            $this->zipcode;
        $addr = str_replace(',,', ',', $addr);
        $this->d_addr = str_replace(', ,', ', ', $addr);

        return $this->d_addr;
    }

    // public function getOrderedDateModifiedAttribute()
    // {
    //     $orderedDate = Carbon::parse($this->ordate)->format('m/d/Y h:i A');
    //     if ($this->ordate) {
    //         $diff = $this->cafe->market->timezone_difference;
    //         $orderedDate = ($diff) ? Carbon::parse($this->ordate)->addHours($diff)->format('m/d/Y h:i A') : $orderedDate;
    //     }

    //     return $orderedDate;
    // }

    public function getOrderedDateModifiedAttribute()
    {
        $orderedDate = Carbon::parse($this->ordate)->format('m/d/Y h:i A');

        if ($this->ordate) {
            $diff = (int) $this->cafe->market->timezone_difference; // cast here
            $orderedDate =
                $diff !== 0 ? Carbon::parse($this->ordate)->addHours($diff)->format('m/d/Y h:i A') : $orderedDate;
        }

        return $orderedDate;
    }

    public function getAuthAmountAttribute()
    {
        return round($this->total + $this->total * 0.2, 2);
    }

    public function reorder($zipcodeRecord)
    {
        if ($this->cart) {
            return new Reorder($this, $zipcodeRecord);
        }
    }

    public function cart()
    {
        return $this->hasOne(Cart::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'alonti_user_id');
    }

    public function cafe()
    {
        return $this->belongsTo(Cafe::class, 'cafe_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function time()
    {
        return $this->belongsTo(Time::class, 'time_id');
    }

    public function mailer()
    {
        return new OrderMailer($this);
    }

    public function paid()
    {
        return $this->hasOne(CimPaid::class, 'order_id');
    }

    public function placedBy()
    {
        return $this->hasOne(User::class, 'id', 'placetoderby');
    }

    public function getDeliveryDateAndTimeAttribute()
    {
        $deliveryTime = $this->time;

        // Add safety checks to prevent array access errors
        if (!$deliveryTime || !$deliveryTime->time) {
            return;
        }

        $divideInterval = explode('-', $deliveryTime->time);

        $firstInterval = $divideInterval[0];
        $hourFormat = explode(' ', trim($firstInterval));
        if (count($hourFormat) < 2) {
            return;
        }

        $hour = explode(':', $hourFormat[0]);
        if (count($hour) < 2) {
            return;
        }

        $convertTo24 = $hourFormat[1] == 'PM' && intval($hour[0]) !== 12 ? intval($hour[0]) + 12 : intval($hour[0]);
        $timezone =
            $this->cafe && $this->cafe->market && $this->cafe->market->timezone_difference
                ? $this->cafe->market->timezone_difference
                : 'UTC';

        try {
            $deliveryDateAndtime = \Carbon\Carbon::create(
                $this->d_date->year,
                $this->d_date->month,
                $this->d_date->day,
                intval($hour[0]),
                intval($hour[1]),
                0,
                $timezone
            );

            return $deliveryDateAndtime;
        } catch (\Exception $e) {
            return;
        }
    }

    public function isOrderLocked()
    {
        $delivery = $this->delivery_date_and_time;
        $now = timezone(\Carbon\Carbon::now(), $this->cafe->market);
        $hoursToDeliver = $now->diffInHours($delivery, false);
        if ($now->isSameDay($delivery)) {
            return true;
        }

        return false;
    }

    public function getManagerDetailsAttribute()
    {
        $cafe = $this->cafe;
        $managerName = $cafe->manager;
        $managerEmail = $cafe->mgremail;
        if ($managerEmail) {
            return [$managerEmail];
        }

        return [];
    }

    public function offmenus()
    {
        return $this->hasMany(Offmenu::class);
    }

    public function orderTrack()
    {
        return $this->hasMany(OrderTrack::class, 'order_id');
    }

    public function reward()
    {
        return $this->hasOne(Reward::class, 'order_id');
    }

    public function getLastDeliveryZipcode($userId)
    {
        return $this->where([
            'alonti_user_id' => $userId,
            'status' => 'Delivered',
        ])
            ->select('zipcode')
            ->orderBy('id', 'desc')
            ->first();
    }

    public function salesAreaBasedOnZipCode()
    {
        Log::info('Inside salesAreaBasedOnZipCode()');

        $zipcodeValue = $this->zipcode ? $this->zipcode : null;
        Log::debug('Zipcode value:', ['zipcode' => $zipcodeValue]);

        if ($zipcodeValue) {
            $zipcode = Zipcode::where('zipcode', $zipcodeValue)->first();
            Log::debug('Zipcode lookup result:', ['zipcodeRecord' => $zipcode]);

            if (!empty($zipcode) && isset($zipcode->district_id)) {
                Log::info('Found district_id from zipcode', ['district_id' => $zipcode->district_id]);

                return $zipcode->district_id;
            }
        }

        $cafeDistrictId = $this->cafe->district_id ?? null;
        Log::info('Fallback to cafe district_id', ['district_id' => $cafeDistrictId]);

        return $cafeDistrictId;
    }

    /**
     * Calculate hours until delivery considering market timezone
     * Returns negative value if delivery has passed
     *
     * @return float Hours until delivery (negative if in the past)
     */
    public function hoursUntilDelivery()
    {
        // Get market timezone offset (e.g., -6, -8, -5)
        $timezoneOffset = (int) ($this->cafe->market->timezone_difference ?? 0);

        // Get current time in market's timezone by adding the offset
        $currentTime = Carbon::now()->addHours($timezoneOffset);

        // Parse delivery time and create delivery datetime
        $deliveryTime = explode('-', $this->time->time);
        $orderDeliveryDate = date('Y-m-d', strtotime($this->d_date)) . ' ' . trim($deliveryTime[0]);
        $deliveryDateTime = Carbon::parse($orderDeliveryDate);

        // Calculate hours until delivery (negative if in the past)
        return $currentTime->diffInHours($deliveryDateTime, false);
    }
}
