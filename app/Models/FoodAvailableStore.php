<?php

declare(strict_types=1);


namespace App\Models;

class FoodAvailableStore extends BaseModel
{
    protected $table = 'food_available_stores';

    public function category()
    {
        return $this->belongsTo(Category::class, 'entity_id')->where(['entity_name' => 'OjCategories']);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'entity_id')->where(['entity_name' => 'OjProducts']);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'entity_id')->where(['entity_name' => 'OjProductVariants']);
    }

    public function option()
    {
        return $this->belongsTo(ProductOption::class, 'entity_id')->where(['entity_name' => 'OjProductOptions']);
    }

    public function getDataBasedEntityCafe($cafeId, $entityId, $entityName)
    {
        $entityId = !is_array($entityId) ? [$entityId] : $entityId;

        return FoodAvailableStore::where([
            'cafe_id' => $cafeId,
            'entity_name' => $entityName,
        ])
            ->whereIn('entity_id', $entityId)
            ->pluck('entity_id')
            ->toArray();
    }
}
