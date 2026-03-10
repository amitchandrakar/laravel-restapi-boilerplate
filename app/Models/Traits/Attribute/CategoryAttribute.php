<?php

declare(strict_types=1);

namespace App\Models\Traits\Attribute;

trait CategoryAttribute
{
    public function getUrlAttribute()
    {
        if (config()->get('app.request-from-invitee')) {
            return url('/invitation/' . $this->uniqueurl->url);
        }

        return url($this->uniqueurl->url);
    }
}
