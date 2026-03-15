<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Api\CartController as ApiCartController;
use App\Http\Controllers\Api\V1\Api\EzCaterController;
use App\Http\Controllers\Api\V1\Api\UserController as ApiUserController;
use App\Http\Controllers\Api\V1\Api\ZipcodeController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\UserController as AuthUserController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\EmailCampaignController;
use App\Http\Controllers\Api\V1\GroupOrderController;
use App\Http\Controllers\Api\V1\Invitation\InviteeController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/home', [CategoryController::class, 'index']); // Home page route
Route::post('/login/verify', [LoginController::class, 'verifyLogin']);

Route::post('unsubscribe', [EmailCampaignController::class, 'unsubscribe']);
Route::post('subscribe', [EmailCampaignController::class, 'subscribe']);

Route::group(['namespace' => 'Auth', 'middleware' => ['auth.delivery', 'no-cache']], function () {
    Route::get('/login', [LoginController::class, 'login'])->name('login');
    Route::get('/guest-checkout', [LoginController::class, 'guestCheckout']);
    Route::get('/register', [LoginController::class, 'register']);
    Route::post('/register', [LoginController::class, 'createRegistration']);
    Route::get('/logout', [LoginController::class, 'logout']);
    Route::post('forgot-password', [LoginController::class, 'forgotPassword']);
    Route::get('reset-password/{hash}', [LoginController::class, 'resetPassword']);
    Route::post('reset-password', [LoginController::class, 'saveResetPassword']);
    Route::get('/login-as-admin/{hashId}', [LoginController::class, 'adminLogin']);

    Route::group(['middleware' => ['auth', 'auth.delivery', 'no-cache']], function () {
        Route::get('/refer-a-friend', [AuthUserController::class, 'customerReferralRewards']);
        Route::group(['prefix' => '/profile'], function () {
            Route::get('/', [AuthUserController::class, 'profile']);
            Route::get('/orders', [AuthUserController::class, 'orders']);
            Route::get('/view-order/{hashid}', [AuthUserController::class, 'viewOrder']);
            Route::get('/edit-order/{hashid}', [AuthUserController::class, 'editOrder']);
            Route::get('/edit-cart/{hashid}', [AuthUserController::class, 'editGroupCart']);
            Route::get('/delete-cart/{hashid}', [AuthUserController::class, 'deleteCart']);
            Route::get('/cancel-order/{hashid}', [AuthUserController::class, 'cancelOrder']);
            Route::get('invoice/download/{hashid}', [AuthUserController::class, 'invoiceDownload']);
            Route::get('/rewards', [AuthUserController::class, 'customerRewards']);
            Route::get('/cash-out', [AuthUserController::class, 'cashOut']);
            Route::get('/referred-customers', [AuthUserController::class, 'referredCustomerStatus']);
            Route::get('/edit-group-cart-from-admin/{id}', [AuthUserController::class, 'editGroupCartFromAdmin']);
        });
        Route::group(['prefix' => '/user'], function () {
            Route::post('/update-phone', [AuthUserController::class, 'updatePhone']);
            Route::post('/update-address', [AuthUserController::class, 'updateAddress']);
            Route::post('/update-cmpy-phone', [AuthUserController::class, 'updateCmpyPhone']);
            Route::post('/update-secondary-phone', [AuthUserController::class, 'updateSecondaryPhone']);
            Route::post('/update-password', [AuthUserController::class, 'updatePassword']);
            Route::get('/attach-house-account', [AuthUserController::class, 'attachHouseAccount']);
            Route::post('/apply-house-account', [AuthUserController::class, 'applyHouseAccount']);
            Route::post('/update-last-name', [AuthUserController::class, 'updateLastName']);
            Route::post('/update-first-name', [AuthUserController::class, 'updateFirstName']);
            Route::post('/update-sms-opt-in', [AuthUserController::class, 'updateSmsOptIn']);
        });
    });
});

Route::group(['prefix' => '/order', 'middleware' => ['auth', 'auth.delivery', 'no-cache']], function () {
    Route::get('/start-new-order', [OrderController::class, 'startNewOrder']);
    Route::get('/start-group-order', [OrderController::class, 'startGroupOrder']);
    Route::get('/reorder/{id}', [OrderController::class, 'reorder']);
    Route::get('/revalidate-receipt/{id}', [OrderController::class, 'updateReceipt']);
    Route::get('/updated-receipt/{hashid}/{campaignTrack?}', [OrderController::class, 'updatedReceipt']);
});

Route::group(['prefix' => '/order', 'middleware' => ['auth.delivery', 'no-cache']], function () {
    Route::get('/validate-receipt/{id}', [OrderController::class, 'checkReceipt']);
    Route::get('/receipt/{hashid}/{campaignTrack?}', [OrderController::class, 'receipt']);
});

Route::group(['prefix' => 'group-order'], function () {
    Route::get('/start', [GroupOrderController::class, 'start'])->middleware('auth.delivery');
    Route::get('/login', [GroupOrderController::class, 'login'])->middleware(['auth.delivery', 'guest']);
    Route::get('/invite-people', [GroupOrderController::class, 'invitePeople']);
    Route::get('/invite-to-order', [GroupOrderController::class, 'inviteToOrder']);
    Route::post('/create-invitee-list', [GroupOrderController::class, 'createInviteList']);
    Route::post('/save-group-name', [GroupOrderController::class, 'saveGroupName']);
    Route::post('/remove-invitee', [GroupOrderController::class, 'removeInvitee']);
    Route::post('/remove-unupdated-invitee', [GroupOrderController::class, 'removeUnupdatedInvitee']);
    Route::post('/send-invitation', [GroupOrderController::class, 'sendInvitation']);
    Route::get('/fetch-invitee-list', [GroupOrderController::class, 'fetchInviteeList']);
    Route::get('/decline/{id}', [GroupOrderController::class, 'decline']);
    Route::get('/accept/{id}', [GroupOrderController::class, 'accept']);
    Route::post('/resend-invite', [GroupOrderController::class, 'resendInvite']);
    Route::post('/remind-invite', [GroupOrderController::class, 'remindInvite']);
    Route::get('/save-start-order', [GroupOrderController::class, 'saveAndStartNewOrder'])->middleware('auth.delivery');
    Route::get('/activate', [GroupOrderController::class, 'activateGroupOrder']);
    Route::post('/send-update-invitation', [GroupOrderController::class, 'updateGroupOrderInvitation']);
    Route::post('/delete-group-name', [GroupOrderController::class, 'deleteGroupName']);
});

Route::group(['prefix' => 'invitation', 'middleware' => ['invitation', 'no-cache']], function () {
    Route::group(['namespace' => 'Invitation'], function () {
        Route::get('/', [InviteeController::class, 'index'])->name('invitee_home');
        Route::get('/invitee-order-complete', [InviteeController::class, 'orderComplete']);
        Route::post('/addName', [InviteeController::class, 'addName']);
    });
    Route::post('/cart/add', [CartController::class, 'add']);
    Route::post('/cart/update', [CartController::class, 'update']);
    Route::get('/summary', [CartController::class, 'index']);

    // Keep this at bottom
    Route::get('/{name}', [ProductController::class, 'index'])->name('allProducts');
});

Route::group(['middleware' => 'auth.delivery'], function () {
    Route::get('/listing', [CategoryController::class, 'listing']);
    Route::post('/cart/add', [CartController::class, 'add']);
    Route::post('/cart/update', [CartController::class, 'update']);
    Route::get('/summary', [CartController::class, 'index'])
        ->name('Summary')
        ->middleware('no-cache');
    Route::get('/delivery-details', [CartController::class, 'delivery'])
        ->name('Delivery')
        ->middleware(['no-cache', 'auth_or_guest_checkout']);
    Route::get('/serving-ware-options', [CartController::class, 'servingOptions'])
        ->name('servingOptions')
        ->middleware(['no-cache', 'auth_or_guest_checkout']);
    Route::get('/payment-details', [CartController::class, 'payment'])
        ->name('Payment')
        ->middleware('no-cache');
    Route::get('/review', [CartController::class, 'review'])
        ->name('Review')
        ->middleware('no-cache');
    Route::group(['middleware' => 'url'], function () {
        Route::get('{name}', [ProductController::class, 'index'])->name('allProducts');
    });
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/delivery-area/search', [ZipcodeController::class, 'search']);
Route::post('/delivery-area/find', [ZipcodeController::class, 'setDeliveryAreaByZipId']);
Route::get('delivery-area/cafe_info', [ZipcodeController::class, 'getdeliveryinfo'])->middleware('auth.delivery');
Route::get('delivery-area/manager_info', [ZipcodeController::class, 'retreiveCateringMgrInfo'])->middleware(
    'auth.delivery'
);
Route::post('cart-item/update', [ApiCartController::class, 'update'])->middleware('auth.delivery');
Route::post('cart-item/delete', [ApiCartController::class, 'delete'])->middleware('auth.delivery');
Route::post('cart/update-personal-msg', [ApiCartController::class, 'updateMsg'])->middleware('auth.delivery');
Route::post('delivery/has-account', [ApiUserController::class, 'hasAccount']);
Route::post('delivery-area/closest-cafe', [ZipcodeController::class, 'pickupClosestCafe']);
// Validate weekend and night orders
Route::post('validate-delivery-date-time', [ZipcodeController::class, 'validateDeliveryDateTime']);
Route::post('delivery-area/store', [ApiCartController::class, 'storeDelivery']);
Route::post('serving-option/store', [ApiCartController::class, 'storeServingOption']);
Route::post('serving-option/delete', [ApiCartController::class, 'deleteServingOption']);
Route::post('payment/store', [ApiCartController::class, 'storePayment']);
Route::post('payment/removeCard', [ApiCartController::class, 'removeCard']);
Route::post('cart/place-order', [ApiCartController::class, 'placeOrder']);
Route::post('cart/storeTipAmount', [ApiCartController::class, 'updateTip']);
Route::post('cart/update-order', [ApiCartController::class, 'updateOrder'])->middleware('auth');
Route::post('cart/add-free-item', [ApiCartController::class, 'addFreeItem']);
Route::post('coupon/apply', [ApiCartController::class, 'applyCoupon']);
Route::post('rewards/apply', [ApiCartController::class, 'applyRewards']);
Route::post('rewards/reset', [ApiCartController::class, 'resetRewards']);
Route::post('coupon/reset', [ApiCartController::class, 'resetCoupon']);
Route::post('cart/special-cookie-validation', [ApiCartController::class, 'splCookieValidation'])->middleware(
    'auth.delivery'
);
Route::post('/user/cashout', [ApiUserController::class, 'cashOut'])->middleware('auth.delivery');
Route::post('/user/updateRewards', [ApiUserController::class, 'updateUserRewardConfig'])->middleware('auth.delivery');
Route::post('/user/refer-customers', [ApiUserController::class, 'referCustomers'])->middleware('auth.delivery');
Route::post('/user/referralCashout', [ApiUserController::class, 'referralCashOut'])->middleware('auth.delivery');
Route::post('cart/save-invitee-default-meal', [CartController::class, 'saveInviteeDefaultMeal'])->middleware(
    'auth.delivery'
);

// Delete user address
Route::post('delivery/delete-account', [ApiUserController::class, 'deleteAddress']);

// Create a group called ezcater and put cancel-order
Route::group(['prefix' => 'ezcater'], function () {
    // Get webhook log
    Route::get('webhook-log/{order_id}', [EzCaterController::class, 'getWebhookLog']);

    // Cancel order
    Route::get('cancel-order', [EzCaterController::class, 'cancelOrder']);

    // Place an order
    Route::get('place-order', [EzCaterController::class, 'placeOrder']);

    // Test api endpoint for faheem to place orders
    Route::get('place-order-offmenu', [EzCaterController::class, 'placeOrderOffmenu']);

    // Place ezcater order
    Route::get('place-ezcater-order', [EzCaterController::class, 'placeEzcaterOrder']);

    // Update ezcater order
    Route::get('update-ezcater-order', [EzCaterController::class, 'updateEzcaterOrder']);

    Route::get('handle-ezcater-order', [EzCaterController::class, 'handleEzcaterOrder']);
});
