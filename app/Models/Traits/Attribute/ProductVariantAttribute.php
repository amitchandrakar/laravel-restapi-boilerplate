<?php

declare(strict_types=1);


namespace App\Models\Traits\Attribute;

trait ProductVariantAttribute
{
    public function getPackageNameAttribute()
    {
        $value = $this->name;
        if (stripos($this->name, 'Premium sweet selections') !== false) {
            $value =
                substr($this->name, 0, strripos($this->name, 'Premium sweet selections')) .
                '<u class="info-icon" id="id-' .
                $this->id .
                '">Premium sweet selections</u>';
        }
        if (stripos($this->name, 'Cookie box') !== false) {
            $value =
                substr($this->name, 0, strripos($this->name, 'Cookie box')) .
                '<u class="info-icon" id="id-' .
                $this->id .
                '">Cookie box</u>';
        }

        return $value;
    }

    public function getTooltipAttribute()
    {
        $value = false;
        if (stripos($this->name, 'premium sweet selections') !== false) {
            $value = true;
        }
        if (stripos($this->name, 'Cookie box') !== false) {
            $value = true;
        }

        return $value;
    }

    public function getPackageOptionAttribute()
    {
        return substr($this->name, 0, strrpos($this->name, ':'));
    }
}
