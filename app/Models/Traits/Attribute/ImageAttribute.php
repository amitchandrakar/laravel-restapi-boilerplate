<?php

declare(strict_types=1);


namespace App\Models\Traits\Attribute;

trait ImageAttribute
{
    public function getImagePathAttribute()
    {
        return $this->getBasePath($this) . '/original/' . $this->filename;
    }

    public function getMediumImagePathAttribute()
    {
        return $this->getBasePath($this) . '/medium/' . $this->filename;
    }

    public function getLargeImagePathAttribute()
    {
        return $this->getBasePath($this) . '/large/' . $this->filename;
    }

    public function getSmallImagePathAttribute()
    {
        return $this->getBasePath($this) . '/small/' . $this->filename;
    }

    public function getProductImagePathAttribute()
    {
        $url = 'https://alonti-dev.s3.amazonaws.com/admin-images/ojproducts/';
        $imagePath = [
            'large' => $url . $this->id . '/large/' . $this->filename,
            'small' => $url . $this->id . '/small/' . $this->filename,
            'medium' => $url . $this->id . '/medium/' . $this->filename,
        ];

        return $imagePath;
    }

    public function getVariantImagePathAttribute()
    {
        $url = 'https://alonti-dev.s3.amazonaws.com/admin-images/ojproductvariants/';
        $imagePath = [
            'large' => $url . $this->id . '/large/' . $this->filename,
            'small' => $url . $this->id . '/small/' . $this->filename,
            'medium' => $url . $this->id . '/medium/' . $this->filename,
        ];

        return $imagePath;
    }
}
