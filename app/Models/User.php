<?php

namespace App\Models;

use App\Alonti\Presenters\UserPresenter;
use App\Alonti\Support\EncryptIdentity;
use App\Mailer\UserMailer;
use Illuminate\Auth\Authenticatable as UserAuthenticatable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

/**
 * User Model
 *
 * Represents a customer/user in the Alonti system with:
 * - Authentication functionality
 * - Order and cart management
 * - Group order leadership
 * - Reward and configuration management
 * - Company and cafe associations
 * - Payment profile integration
 *
 * @property int|null $id
 * @property string|null $uuid
 * @property string|null $email
 * @property string|null $fname
 * @property string|null $lname
 * @property string|null $phone
 * @property string|null $cmpy_phone
 * @property string|null $addr
 * @property string|null $addr2
 * @property string|null $city
 * @property string|null $state
 * @property string|null $zip
 * @property string|null $physical_addr
 * @property string|null $physical_addr2
 * @property string|null $physical_city
 * @property string|null $physical_state
 * @property string|null $physical_zip
 * @property string|null $secondary_phone
 * @property int|null $active_cart_id
 * @property int|null $cafe_id
 * @property int|null $company_user_id
 * @property int|null $customermenu_id
 * @property int|null $group_id
 * @property int|null $payment_id
 * @property int|null $type
 * @property int|null $account_status
 * @property int|null $social_login
 * @property string|null $dob
 * @property string|null $company_name
 * @property mixed $salary
 * @property string|null $contact_number
 * @property int|string|null $status
 * @property string|int|null $account_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \App\Models\UserConfiguration|null $myconfig
 * @property \App\Models\Cim|null $cim
 * @property Collection<int, \App\Models\Cart> $carts
 * @property Collection<int, \App\Models\Order> $orders
 * @property Collection<int, \App\Models\GroupOrder> $group_orders
 */
class User extends BaseModel implements Authenticatable
{
    use EncryptIdentity, HasApiTokens, HasPermissions, HasRoles, Notifiable, UserAuthenticatable;

    const CREATED_AT = 'creation_date';

    const UPDATED_AT = 'last_updated';

    protected $table = 'alonti_users';

    protected static $unguarded = true;

    /**
     * Get the name of the unique identifier for authentication
     *
     * Uses email as the authentication identifier instead of default ID.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'email';
    }

    /**
     * Get user's full name attribute
     *
     * Combines first and last name into a single display name.
     *
     * @return string Full name
     */
    public function getNameAttribute()
    {
        return $this->fname . ' ' . $this->lname;
    }

    /**
     * Get the unique identifier for authentication
     *
     * Returns email address as the authentication identifier.
     *
     * @return string Email address
     */
    public function getAuthIdentifier()
    {
        return $this->email;
    }

    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Get user's payment profile (CIM - Customer Information Manager)
     *
     * Returns the most recent payment profile for the user.
     * Used for stored payment method management.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function cim()
    {
        return $this->hasOne(Cim::class, 'alonti_user_id')->orderBy('id', 'desc');
    }

    /**
     * Get user data formatted for frontend views
     *
     * Returns essential user information as JSON for use in JavaScript/frontend.
     * Excludes sensitive information like passwords.
     *
     * @return string JSON encoded user data
     */
    public function jsonForView()
    {
        $user = [
            'fname' => $this->fname,
            'lname' => $this->lname,
            'phone' => $this->phone,
            'cmpy_phone' => $this->cmpy_phone,
            'addr' => $this->addr,
            'physical_addr' => $this->physical_addr,
            'email' => $this->email,
        ];

        return json_encode($user);
    }

    /**
     * Update user's active cart ID
     *
     * Sets the current cart as the user's active cart for session management.
     * Used when creating new carts or switching between carts.
     *
     * @param  Cart  $cart  Cart to set as active
     * @return void
     */
    public static function updateActiveCartId($cart)
    {
        if ($cart->user_id) {
            $user = User::find($cart->user_id);
            $user->active_cart_id = $cart->id;
            $user->save();
        }
    }

    /**
     * Get mailer instance for user-related emails
     *
     * Returns a mailer instance for sending user-specific emails like
     * confirmations, notifications, and promotional messages.
     *
     * @return UserMailer
     */
    public function mailer()
    {
        return new UserMailer($this);
    }

    /**
     * Get presenter instance for user data formatting
     *
     * Returns a presenter for formatting user data for display purposes.
     *
     * @return UserPresenter
     */
    public function presenter()
    {
        return new UserPresenter($this);
    }

    /**
     * Get user's orders relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'alonti_user_id');
    }

    /**
     * Get user's carts relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function carts()
    {
        return $this->hasMany(Cart::class, 'user_id');
    }

    public function cafe()
    {
        return $this->belongsTo(Cafe::class, 'cafe_id');
    }

    /**
     * Get user's group orders relationship (as leader)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function group_orders()
    {
        return $this->hasMany(GroupOrder::class, 'user_id');
    }

    public function companyList()
    {
        return $this->belongsTo(CompanyUser::class, 'company_user_id');
    }

    /**
     * Get user's active group order carts
     *
     * Returns carts that are group orders and haven't been placed as orders yet.
     * Used for managing ongoing group order leadership.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function activeGroupOrder()
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, Cart> $collection */
        $collection = $this->carts()->whereNull('order_id')->whereNotNull('group_order_id')->get();

        return $collection;
    }

    /**
     * Get count of user's orders
     *
     * Returns total number of orders placed by this user.
     * Used for first-time customer detection and loyalty tracking.
     *
     * @return int Number of orders
     */
    public function orderCount()
    {
        return $this->orders()->count();
    }

    /**
     * Get user's rewards relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reward()
    {
        return $this->hasMany(Reward::class, 'user_id');
    }

    /**
     * Get user's configuration settings
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function myconfig()
    {
        return $this->hasOne(UserConfiguration::class, 'user_id');
    }

    public function abandonedCart()
    {
        return $this->hasMany(AbandonedCart::class, 'alonti_user_id');
    }
}
