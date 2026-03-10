<?php

declare(strict_types=1);

namespace App\Models;

class Market extends BaseModel
{
    public $timestamps = false;

    public function directors()
    {
        return $this->hasMany(Director::class, 'market_id');
    }
}
