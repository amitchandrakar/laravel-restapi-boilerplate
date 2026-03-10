<?php

declare(strict_types=1);

namespace App\Models;

class District extends BaseModel
{
    public function cafes()
    {
        return $this->hasMany(Cafe::class, 'district_id');
    }

    public function market()
    {
        return $this->belongsTo(Market::class, 'market_id');
    }
}
