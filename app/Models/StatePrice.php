<?php

declare(strict_types=1);


namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class StatePrice extends BaseModel
{
    use SoftDeletes;

    protected $table = 'oj_states_prices';

    public static function getPrice($entityId, $entityType, $stateId)
    {
        return StatePrice::where([
            'entity_id' => $entityId,
            'entity_type' => $entityType,
            'state_id' => $stateId,
        ])->first();
    }

    public function item()
    {
        return $this->hasMany(CartItem::class, 'state_price_id')->where(['entity_type' => 'OjProductVariants']);
    }

    public function item_options()
    {
        return $this->hasMany(CartOption::class, 'state_price_id')->where(['entity_type' => 'OjProductSelections']);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'entity_id')->where(['entity_type' => 'OjProductVariants']);
    }

    public function selection()
    {
        return $this->belongsTo(ProductSelection::class, 'entity_id')->where(['entity_type' => 'OjProductSelections']);
    }

    public function package()
    {
        return $this->belongsTo(Package::class, 'entity_id')->where(['entity_type' => 'OjPackageSizes']);
    }
}
