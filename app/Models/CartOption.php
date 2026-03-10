<?php

declare(strict_types=1);

namespace App\Models;

class CartOption extends BaseModel
{
    protected $table = 'oj_cart_options';

    protected static $unguarded = true;

    public function updateOptionPrice($stateId)
    {
        $selectionPrice = StatePrice::where([
            'entity_id' => $this->product_selection_id,
            'entity_type' => config('custom.entitytype.selection'),
            'state_id' => $stateId,
        ])->first();
        $this->unit_price = $selectionPrice->price;
        $this->total = $selectionPrice->price * $this->quantity;
        $this->state_price_id = $selectionPrice->id;
        $this->save();

        return true;
    }

    public static function deleteByCartItemId($cartItemId)
    {
        CartOption::where('cart_item_id', $cartItemId)->delete();
    }

    public function item()
    {
        return $this->belongsTo(CartItem::class, 'cart_item_id');
    }

    public function option()
    {
        return $this->belongsTo(ProductOption::class, 'product_option_id');
    }

    public function selection()
    {
        return $this->belongsTo(ProductSelection::class, 'product_selection_id');
    }

    public function statePrice()
    {
        return $this->belongsTo(StatePrice::class, 'state_price_id')->where(['entity_type' => 'OjProductSelections']);
    }

    public function selectionWithoutSoftDelete()
    {
        return $this->selection()->withTrashed();
    }

    public function updateSelectionFree()
    {
        $this->is_free = 0;
        $this->save();
    }
}
