<?php

declare(strict_types=1);

namespace App\Alonti\Order;

use App\Models\Cart;
use App\Models\CartInvitee;
use App\Models\CartItem;
use App\Models\CartOption;
use App\Models\Order;
use App\Models\ProductOptionSelection;
use App\Models\Zipcode;
use Illuminate\Support\Facades\Auth;

class ReOrder
{
    public $order;

    public $zipcodeRecord;

    public $cafe;

    public $cartRecord;

    public $cart;

    public $cartInviteeRecord;

    public $cartInvitee;

    public $cartItem;

    public function __construct(Order $order, Zipcode $zipcodeRecord)
    {
        $this->order = $order;
        $this->zipcodeRecord = $zipcodeRecord;
        $this->cafe = $this->zipcodeRecord->cafe;
        $this->createCart();
    }

    public function createCart()
    {
        $createCartInvitee = false;

        if ($this->order->group_order_id) {
            $cart = [
                'user_id' => Auth::user()->id,
                'cafe_id' => $this->order->cafe->id,
                'zipcode' => $this->zipcodeRecord->zipcode,
                'state_id' => $this->zipcodeRecord->state_id,
                'group_order_id' => $this->order->group_order_id,
                'order_name' => $this->order->orderName,
            ];
            $oldCartInvitees = $this->order->cart->invitees;
            $createCartInvitee = true;
        } else {
            $cart = [
                'user_id' => Auth::user()->id,
                'cafe_id' => $this->order->cafe->id,
                'zipcode' => $this->zipcodeRecord->zipcode,
                'state_id' => $this->zipcodeRecord->state_id,
            ];
        }

        $cartRecord = Cart::create($cart);

        if ($createCartInvitee) {
            foreach ($oldCartInvitees as $invitee) {
                if ($invitee->response == CartInvitee::RESPONSE_COMPLETED) {
                    $cartInvitee = [];
                    $cartInvitee = [
                        'cart_id' => $cartRecord->id,
                        'invitee_id' => $invitee->invitee_id,
                        'group_order_id' => $invitee->group_order_id,
                        'response' => $invitee->response,
                    ];
                    $cartInviteeRecord = CartInvitee::create($cartInvitee);
                }
            }
        }

        $user = auth()->user();
        $user->active_cart_id = $cartRecord->id;
        $user->save();

        $this->createCartItems($cartRecord);
        $cartRecord->updateDeliveryArea($cartRecord->zipcode, $cartRecord->state_id, $cartRecord->cafe_id);
        $this->cartRecord = $cartRecord;
    }

    private function createCartItems($cartRecord)
    {
        $cartItems = $this->order->cart->items()->withoutAddons()->with('addons')->get();

        $cartItems->each(function (CartItem $cartItem) use ($cartRecord) {
            if (
                $cartItem->product &&
                $cartItem->product->status &&
                !$cartItem->product->deleted_at &&
                ($cartItem->variant && $cartItem->variant->status && !$cartItem->variant->deleted_at)
            ) {
                $newCartItem = $this->createSingleCartItem($cartRecord, $cartItem);
                // Insert Addons Separately

                if ($cartItem->addons->isNotEmpty()) {
                    $cartItem->addons->each(function ($addonItem) use ($cartRecord, $newCartItem) {
                        if (
                            $addonItem->product &&
                            $addonItem->product->status &&
                            !$addonItem->product->deleted_at &&
                            ($addonItem->variant && $addonItem->variant->status && !$addonItem->variant->deleted_at)
                        ) {
                            $this->createSingleCartItem($cartRecord, $addonItem, $newCartItem);
                        }
                    });
                }
            }
        });
    }

    private function createSingleCartItem($cartRecord, $cartItem, $parentItem = null)
    {
        $cartItemInfo = [
            'cart_id' => $cartRecord->id,
            'addon_cartitem_id' => $parentItem ? $parentItem->id : null,
            'category_id' => $cartItem->category_id,
            'product_id' => $cartItem->product_id,
            'product_variant_id' => $cartItem->product_variant_id,
            'product_package_id' => $cartItem->product &&
                $cartItem->variant &&
                $cartItem->product_package_id &&
                $cartItem->product->package &&
                !$cartItem->product->package->deleted_at
                    ? $cartItem->product_package_id
                    : null,
            'package_price' => $cartItem->product &&
                $cartItem->variant &&
                $cartItem->product_package_id &&
                $cartItem->product->package &&
                !$cartItem->product->package->deleted_at
                    ? $cartItem->package_price
                    : null,
            'package_size' => $cartItem->product &&
                $cartItem->variant &&
                $cartItem->product_package_id &&
                $cartItem->product->package &&
                !$cartItem->product->package->deleted_at
                    ? $cartItem->package_size
                    : null,
            'product_dietary_id' => $cartItem->product_dietary_id,
            'quantity' => $cartItem->quantity,
            'unit_price' => $cartItem->unit_price,
            'total' => $cartItem->total,
            'product_description' => $cartItem->product->description,
            'product_name' => $cartItem->product->name,
            'box_lunch_type' => $cartItem->box_lunch_type,
            'state_price_id' => $cartItem->state_price_id,
            'invitee_id' => $cartItem->invitee_id,
            'package_state_price_id' => $cartItem->product &&
                $cartItem->variant &&
                $cartItem->product_package_id &&
                $cartItem->product->package &&
                !$cartItem->product->package->deleted_at
                    ? $cartItem->package_state_price_id
                    : null,
            'who_is_this_for' => $cartItem->who_is_this_for,
            'special_instruction' => $cartItem->special_instruction,
        ];

        $newCartItemRecord = $cartRecord->items()->create($cartItemInfo);

        $cartItem->options->each(function (CartOption $option) use ($newCartItemRecord) {
            $optionSelectionActive = ProductOptionSelection::where([
                'product_option_id' => $option->product_option_id,
                'product_selection_id' => $option->product_selection_id,
            ])->first();

            if (
                $option->option &&
                $option->option->status &&
                !$option->option->deleted_at &&
                ($option->selection && $option->selection->status && !$option->selection->deleted_at) &&
                $optionSelectionActive
            ) {
                $this->createCartItemOption($newCartItemRecord, $option);
            }
        });

        return $newCartItemRecord;
    }

    private function createCartItemOption($newCartItemRecord, $oldOption)
    {
        $item = CartItem::find($newCartItemRecord->id);
        $minServe = $item->product->minimum_serve;
        $qty = $item->quantity / $minServe;
        $newOption = $newCartItemRecord->options()->create([
            'product_option_id' => $oldOption->product_option_id,
            'product_selection_id' => $oldOption->product_selection_id,
            'name' => $oldOption->selection->name,
            'unit_price' => $oldOption->unit_price,
            'quantity' => $qty,
            'total' => $qty * $oldOption->unit_price,
            'state_price_id' => $oldOption->state_price_id,
        ]);

        return $newOption;
    }
}
