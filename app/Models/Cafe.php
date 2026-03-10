<?php

declare(strict_types=1);


namespace App\Models;

class Cafe extends BaseModel
{
    public function zipcode()
    {
        $this->hasMany(Zipcode::class, 'cafe_id', 'cafenum');
    }

    public function order()
    {
        return $this->hasMany(Order::class, 'cafe_id');
    }

    public function user()
    {
        return $this->hasMany(User::class, 'cafe_id');
    }

    public function market()
    {
        return $this->hasOne(Market::class, 'id', 'market_id');
    }

    public function support()
    {
        return $this->hasOne(Cafe::class, 'id', 'supported_by');
    }

    public function csmUser()
    {
        return $this->hasOne(User::class, 'id', 'csm_usrid');
    }

    public function district()
    {
        return $this->hasOne(District::class, 'id', 'district_id');
    }

    // This is a caterering sales manager
    public function director()
    {
        return $this->belongsTo(Director::class, 'catering_manager', 'id');
    }

    // This is a assistant caterering sales manager
    public function directorTwo()
    {
        return $this->hasOne(Director::class, 'id', 'catering_manager2');
    }
}
