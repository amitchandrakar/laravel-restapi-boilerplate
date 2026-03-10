<?php

declare(strict_types=1);

namespace App\Models\Traits\Attribute;

trait ProductSelectionAttribute
{
    public function getSelectionNameAttribute()
    {
        $value = sentenceCase($this->name);
        if ($this->statePrice && $this->statePrice->price > 0) {
            $value = sentenceCase($this->name) . '- Add ' . $this->statePrice->price;
        }

        return $value;
    }

    public function getNameAttribute($value)
    {
        return sentenceCase($value);
    }
}
