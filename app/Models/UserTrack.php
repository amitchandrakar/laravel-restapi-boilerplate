<?php

declare(strict_types=1);


namespace App\Models;

class UserTrack extends BaseModel
{
    protected static $unguarded = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
