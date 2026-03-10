<?php

declare(strict_types=1);

namespace App\Alonti\Invitation;

use App\Models\CartInvitee;
use App\Models\CartItem;
use App\Models\GroupOrder;
use App\Models\Product;

/**
 * Invitation Manager Service
 *
 * Core service for managing group order invitations and invitee sessions:
 * - Session management for invited users in group orders
 * - Cart and order context for invitees
 * - Access to group order relationships (leader, cart, budget)
 * - Invitee-specific cart item counting with complex logic
 * - Integration between invitees and group order workflow
 * - Session cleanup and expiration handling
 */
class InvitationManager
{
    /**
     * Flag to control invitation session expiration
     *
     * @var bool
     */
    public $expireInvitation;

    /**
     * Initialize InvitationManager
     *
     * Sets default expiration flag to false, preserving invitation sessions
     * unless explicitly marked for expiration.
     */
    public function __construct()
    {
        $this->expireInvitation = false;
    }

    /**
     * Create invitation session for a cart invitee
     *
     * Establishes session context for an invited user to participate in group order:
     * - Stores invitee identification information
     * - Links to the group order cart and configuration
     * - Provides budget information for spending limits
     * - Enables invitee-specific cart operations
     *
     * This session allows invitees to add items to the group cart within their budget.
     *
     * @param  CartInvitee  $cartInvitee  The invitee record to create session for
     * @return void
     */
    public function createSessionFor(CartInvitee $cartInvitee)
    {
        $session_key = 'invitation';
        session()->put($session_key, [
            'invitee_id' => $cartInvitee->invitee_id,
            'cart_invitee_id' => $cartInvitee->id,
            'cart_id' => $cartInvitee->cart_id,
            'group_order_id' => $cartInvitee->group_order_id,
            'invitee_budget' => $cartInvitee->cart->groupOrderConfig
                ? $cartInvitee->cart->groupOrderConfig->invitee_budget
                : 0,
        ]);
    }

    /**
     * Get the group order cart for current invitee
     *
     * Retrieves the shared cart that the invitee is contributing to.
     * Used by CartManager when request is from an invitee.
     *
     * @return Cart The group order cart
     */
    public function getCart()
    {
        return CartInvitee::find(session()->get('invitation.cart_invitee_id'))->cart;
    }

    /**
     * Get the invitee user record
     *
     * Retrieves the user who was invited to participate in the group order.
     * Includes soft-deleted users to handle cases where invitee accounts
     * may have been deactivated after invitation.
     *
     * @return User|null The invitee user (including soft-deleted)
     */
    public function getInvitee()
    {
        return CartInvitee::find(session()->get('invitation.cart_invitee_id'))
            ->invitee()
            ->withTrashed()
            ->first();
    }

    /**
     * Get the group order leader
     *
     * Retrieves the user who created and manages the group order.
     * Used for displaying leader information and managing group order permissions.
     *
     * @return User The group order leader
     */
    public function getLeader()
    {
        return GroupOrder::find(session('invitation.group_order_id'))->leader;
    }

    /**
     * Get cart item count for current invitee
     *
     * Calculates the total number of items the current invitee has added to the group cart:
     * - Products with minimum_serve >= 10 are counted as single items regardless of quantity
     * - Other products are counted by their actual quantity
     * - Excludes add-on items from the count (uses withoutAddons scope)
     * - Returns rounded count for display purposes
     *
     * This counting logic matches the Cart model's getCartCount method for consistency.
     *
     * @return int Total count of cart items for the invitee
     */
    public function getInviteeCartCount()
    {
        // Get products that are counted as single items regardless of quantity
        $moreServeProduct = Product::where('minimum_serve', 10)->pluck('id')->toArray();
        $itemCount = 0;

        // Count minimum serve products as single items
        $itemCount += CartItem::where([
            'cart_id' => session('invitation.cart_id'),
            'invitee_id' => session('invitation.invitee_id'),
        ])
            ->whereIn('product_id', $moreServeProduct)
            ->count();

        // Sum quantities for regular products, excluding add-ons
        $itemCount += CartItem::where([
            'cart_id' => session('invitation.cart_id'),
            'invitee_id' => session('invitation.invitee_id'),
        ])
            ->whereNotIn('product_id', $moreServeProduct)
            ->withoutAddons()
            ->sum('quantity');

        return round($itemCount);
    }

    /**
     * Clear invitation session if marked for expiration
     *
     * Removes invitation session data when the expiration flag is set.
     * Currently only clears if explicitly marked for expiration,
     * preserving invitation sessions by default.
     *
     * This method could be enhanced to handle automatic expiration
     * based on time limits or order completion.
     *
     * @return void
     */
    public function clear()
    {
        if ($this->expireInvitation) {
            session()->forget('invitation');
        }
    }
}
