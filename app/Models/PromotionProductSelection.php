<?php

declare(strict_types=1);

namespace App\Models;

class PromotionProductSelection extends BaseModel
{
    protected $table = 'promotion_product_selections';

    public function selectionProducts()
    {
        return $this->belongsTo(PromotionTypeProduct::class, 'promotion_type_product_id');
    }
}
