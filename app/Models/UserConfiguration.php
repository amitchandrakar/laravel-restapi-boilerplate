<?php

declare(strict_types=1);


namespace App\Models;

class UserConfiguration extends BaseModel
{
    protected $table = 'user_configurations';

    protected static $unguarded = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
