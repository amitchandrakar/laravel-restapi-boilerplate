<?php

declare(strict_types=1);


namespace App\Models;

class PromotionType extends BaseModel
{
    protected $table = 'promotion_type';

    public function promotionTypeProduct()
    {
        return $this->hasMany(PromotionTypeProduct::class, 'promotion_type_id');
    }
}
