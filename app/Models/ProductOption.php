<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\Attribute\ProductOptionAttribute;
use App\Models\Traits\Scope\CustomScope;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductOption extends BaseModel
{
    use CustomScope, ProductOptionAttribute, SoftDeletes;

    protected $table = 'oj_product_options';

    const ENTITY_TYPE = 'OjProductOptions';

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id')->availableInStore();
    }

    public function selections()
    {
        return $this->belongsToMany(
            ProductSelection::class,
            'oj_product_option_selections',
            'product_option_id',
            'product_selection_id'
        )->whereNull('oj_product_option_selections.deleted_at')
            ->orderBy('oj_product_selections.display_order')
            ->orderBy('oj_product_selections.id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id')->availableInStore();
    }

    public function item_options()
    {
        return $this->hasMany(CartOption::class, 'product_option_id');
    }

    public function selection()
    {
        return $this->hasAndBelongsToMany(ProductSelection::class, 'product_selection_id');
    }

    public function availableStore()
    {
        return $this->hasMany('App\Models\FoodAvailableStore', 'entity_id')->where([
            'entity_name' => self::ENTITY_TYPE,
        ]);
    }

    public function getOptionById($id)
    {
        $id = is_array($id) ? $id : [$id];

        return ProductOption::whereIn('id', $id)->with('selections')->get();
    }
}
