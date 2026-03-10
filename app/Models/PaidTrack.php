<?php

declare(strict_types=1);

namespace App\Models;

class PaidTrack extends BaseModel
{
    protected static $unguarded = true;

    public function paid()
    {
        return $this->belongsTo(CimPaid::class, 'paid_id');
    }
}
