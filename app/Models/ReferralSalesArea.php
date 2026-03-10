<?php

declare(strict_types=1);


namespace App\Models;

class ReferralSalesArea extends BaseModel
{
    protected $table = 'referral_sales_areas';

    protected static $unguarded = true;

    public function district()
    {
        return $this->belongsTo(District::class, 'sales_area_id');
    }
}
