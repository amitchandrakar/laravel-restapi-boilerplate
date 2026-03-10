<?php

declare(strict_types=1);


namespace App\Models;

use Illuminate\Notifications\Notifiable;

class Director extends BaseModel
{
    use Notifiable;

    public function cafe()
    {
        return $this->hasMany(Cafe::class, 'catering_manager');
    }
}
