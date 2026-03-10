<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\Attribute\ProductVariantAttribute;
use App\Models\Traits\Scope\CustomScope;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends BaseModel
{
    use CustomScope, ProductVariantAttribute, SoftDeletes;

    const ENTITY_TYPE = 'OjProductVariants';

    protected $table = 'oj_product_variants';

    public static function getPackageIdByName($name, $productId)
    {
        return ProductVariant::where(['name' => $name, 'product_id' => $productId])
            ->active()
            ->pluck('id')
            ->first();
    }

    public function item()
    {
        return $this->belongsTo(CartItem::class, 'product_variant_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id')->availableInStore();
    }

    public function option()
    {
        return $this->hasMany(ProductOption::class, 'product_variant_id')->availableInStore(); // ->with(['selections']);
    }

    public function package()
    {
        return $this->hasMany(Package::class, 'product_variant_id');
    }

    public function packageSizes()
    {
        return $this->hasMany(PackageSize::class, 'product_variant_id');
    }

    public function options()
    {
        return $this->hasMany(ProductOption::class, 'product_variant_id')->availableInStore();
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class, 'product_variant_id');
    }

    public function addons()
    {
        return $this->belongsToMany(Product::class, 'oj_product_add_ons', 'product_variant_id', 'addon_product_id')
            ->whereNull('oj_product_add_ons.deleted_at')
            ->availableInStore();
    }

    public function statePrice()
    {
        return $this->hasOne(StatePrice::class, 'entity_id')->where(['entity_type' => self::ENTITY_TYPE]);
    }

    public function image()
    {
        return $this->hasOne(Image::class, 'entity_id')->where(['entity_type' => self::ENTITY_TYPE]);
    }

    public function availableStore()
    {
        return $this->hasMany('App\Models\FoodAvailableStore', 'entity_id')->where([
            'entity_name' => self::ENTITY_TYPE,
        ]);
    }

    public function getVariantById($id)
    {
        $id = is_array($id) ? $id : [$id];

        return ProductVariant::whereIn('id', $id)->get();
    }
}
