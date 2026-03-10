<?php

declare(strict_types=1);

namespace App\Models;

class Package extends BaseModel
{
    protected $table = 'oj_package_sizes';

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id', 'id');
    }

    public function state_price()
    {
        return $this->hasMany(StatePrice::class, 'entity_id')->where(['entity_type' => 'OjPackageSizes']);
    }
}
