<?php

declare(strict_types=1);


namespace App\Models\Traits\Attribute;

trait ProductOptionAttribute
{
    public function getErrorMessageAttribute()
    {
        $value = $this->name;
        if (stripos($this->name, 'Choose') === false) {
            $value = 'choose ' . $this->name;
        }

        return 'Please ' . strtolower($value) . ' to proceed';
    }
}
