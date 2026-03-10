<?php

declare(strict_types=1);

namespace App\Models;

class Zipcode extends BaseModel
{
    protected $table = 'zip_codes';

    public function latlong()
    {
        return $this->belongsTo(ZipcodeLatlan::class, 'zipcode', 'ZipCode');
    }

    public function cafe()
    {
        return $this->belongsTo(Cafe::class, 'cafe_id', 'cafenum');
    }

    // district
    public function district()
    {
        return $this->belongsTo(District::class, 'district_id', 'id');
    }
}
