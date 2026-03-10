<?php

declare(strict_types=1);

namespace App\Alonti\Coupon;

class CouponResponse
{
    public function getFailureResponse($message)
    {
        $data['status'] = false;
        $data['message'] = $message;

        return $data;
    }

    public function getSuccessResponse($message, $data = [])
    {
        $data['status'] = true;
        $data['message'] = $message;

        return $data;
    }
}
