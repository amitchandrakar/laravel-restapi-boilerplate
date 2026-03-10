<?php

declare(strict_types=1);

namespace App\Models;

class SalesArea extends BaseModel
{
    public function cafes()
    {
        return $this->belongsTo(Cafe::class, 'cafenum');
    }
}
