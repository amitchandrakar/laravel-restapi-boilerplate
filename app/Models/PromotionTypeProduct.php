<?php

declare(strict_types=1);

namespace App\Models;

class PromotionTypeProduct extends BaseModel
{
    protected $table = 'promotion_type_product';

    public static function getPromoProduct($promotionTypeId, $productId)
    {
        return PromotionTypeProduct::where([
            'promotion_type_id' => $promotionTypeId,
            'product_id' => $productId,
        ])
            ->with(['productSelections'])
            ->first();
    }

    public function productSelections()
    {
        return $this->hasMany(PromotionProductSelection::class, 'promotion_type_product_id');
    }
}
