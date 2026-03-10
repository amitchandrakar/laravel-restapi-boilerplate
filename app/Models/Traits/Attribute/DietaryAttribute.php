<?php

declare(strict_types=1);

namespace App\Models\Traits\Attribute;

trait DietaryAttribute
{
    public function getDietaryNameAttribute()
    {
        $value = $this->name;
        if ($this->pivot->type == 2) {
            $value .= ' Option Included';
        }

        return $value;
    }
}
