<?php

declare(strict_types=1);


namespace App\Models;

use App\Models\Traits\Attribute\ProductSelectionAttribute;
use App\Models\Traits\Scope\CustomScope;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSelection extends BaseModel
{
    use CustomScope, ProductSelectionAttribute, SoftDeletes;

    const ENTITY_TYPE = 'OjProductSelections';

    protected $table = 'oj_product_selections';

    public function option()
    {
        return $this->hasAndBelongsToMany(ProductOption::class, 'product_option_id');
    }

    public function state_price()
    {
        return $this->hasMany(StatePrice::class, 'entity_id')->where(['entity_type' => self::ENTITY_TYPE]);
    }

    public function item_selection()
    {
        return $this->belongsTo(CartOption::class, 'product_selection_id', 'id');
    }

    public function image()
    {
        return $this->hasOne(Image::class, 'entity_id')->where(['entity_type' => self::ENTITY_TYPE]);
    }

    public function statePrice()
    {
        return $this->hasOne(StatePrice::class, 'entity_id')->where(['entity_type' => self::ENTITY_TYPE]);
    }

    public function dietary()
    {
        return $this->belongsToMany(
            Dietary::class,
            'oj_product_selection_dietaries',
            'product_selection_id',
            'dietary_id'
        )->whereNull('oj_product_selection_dietaries.deleted_at');
    }
}
