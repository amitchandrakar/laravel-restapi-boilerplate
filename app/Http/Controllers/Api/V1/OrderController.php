<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Alonti\ZipManager\ZipManager;
use App\Models\Cart;
use App\Models\Category;
use App\Models\CustomerReferral;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ReferralSalesArea;
use App\Models\Time;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Order Controller
 *
 * Handles order-related operations including:
 * - Starting new individual and group orders
 * - Re-ordering from previous orders
 * - Order receipt display and email notifications
 * - Order confirmation and tracking
 */
class OrderController extends Controller
{
    /**
     * Start a new individual order
     *
     * Creates a new cart for the authenticated user or reuses existing pending cart.
     * Validates no active order is in edit mode before creating new order.
     *
     * @param  ZipManager  $zipManager  Service for ZIP code and delivery area management
     * @return \Illuminate\Http\RedirectResponse
     */
    public function startNewOrder(ZipManager $zipManager)
    {
        // Check if user has an active cart with existing order in edit mode
        $user = auth()->user()->fresh();
        if ($user->active_cart_id) {
            $userActiveCart = Cart::find($user->active_cart_id);
            if ($userActiveCart && $userActiveCart->order_id) {
                return redirect('/profile/orders')->with(
                    'notify-failure',
                    'Already this order #' .
                        $userActiveCart->order_id .
                        ' is in edit mode. Please do update that and proceed.'
                );
            }
        }
        // Find existing pending individual cart or create new one
        $cart = Cart::individual()->mine()->pending()->first();
        if (!$cart) {
            // Set delivery area based on user's ZIP code
            $zipManager->setDeliveryAreaByZip(auth()->user()->zip);
            $state_id = $zipManager->getDeliveryZipcodeStateId();

            $cartRecord = [
                'user_id' => auth()->user()->id,
                'zipcode' => auth()->user()->zip,
                'state_id' => $state_id,
            ];

            $cart = Cart::create($cartRecord);
        }
        // Set the cart as user's active cart
        $user->active_cart_id = $cart->id;
        $user->save();

        return redirect('/');
    }

    /**
     * Start a new group order
     *
     * Redirects to the group order creation flow where users can set up
     * group order parameters and invite participants.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function startGroupOrder()
    {
        return redirect('/group-order/start');
    }

    /**
     * Re-order from a previous order
     *
     * Creates a new cart with items from a previous order. Validates item availability
     * and delivery area before allowing reorder.
     *
     * @param  ZipManager  $zipManager  Service for ZIP code and delivery area validation
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reorder(ZipManager $zipManager)
    {
        // Clear any existing individual cart before reordering
        $individualCart = Cart::individual()->mine()->pending()->first();
        if ($individualCart) {
            $individualCart->discardCart();
        }
        // Get the order to reorder and validate item availability
        $orderId = request('id');
        $order = Order::find($orderId);
        $cart = new Cart();

        // Check if all items from the original order are still available
        $allowReOrder = $cart->cartItemsAndOptionsCurrentStatus($order->cart->items);
        if (!$allowReOrder) {
            return redirect('/profile/orders')->with(
                'notify-failure',
                'Some of your menu items are unavailable. Please contact your kitchen (' . $order->cafe->phone . ').'
            );
        }
        // Validate delivery area for reorder
        $user = Auth::user();
        $zipRecord = $zipManager->getAlontiDeliveryArea();
        if (!$zipRecord) {
            return redirect('profile/orders')->with(
                'notify-failure',
                'Your postal code lies outside our normal delivery area'
            );
        }
        // Create the reorder and redirect to cart summary
        $reorder = $order->reorder($zipRecord);
        if (!$reorder) {
            return redirect('profile/orders')->with('notify-failure', 'Your order does not have any items');
        }

        return redirect('/summary')->with('notify-success', 'Your order has been re-ordered successfully.');
    }

    /**
     * Redirect to order receipt with encrypted ID
     *
     * Generates secure receipt URL using encrypted order ID and includes
     * campaign tracking if promotional codes were used.
     *
     * @param  int  $id  Order ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function checkReceipt($id)
    {
        // Get order and create secure receipt URL with campaign tracking
        $order = Order::find($id);
        $hashId = $order->encrypted_id;

        $campaignTracking = '';
        if ($order->cart->promotion_type_id) {
            $campaignTracking = '/utm_source=promo_code&utm_campaign=' . urlencode($order->cart->promotionType->name);
        }

        return redirect('order/receipt/' . $hashId . $campaignTracking);
    }

    /**
     * Redirect to updated order receipt with encrypted ID
     *
     * Similar to checkReceipt but for modified orders. Includes campaign tracking
     * for promotional code analytics.
     *
     * @param  int  $id  Order ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateReceipt($id)
    {
        // Get order and create secure updated receipt URL
        $order = Order::find($id);
        $hashId = $order->encrypted_id;

        $campaignTracking = '';
        if ($order->cart->promotion_type_id) {
            $campaignTracking = '/utm_source=promo_code&utm_campaign=' . urlencode($order->cart->promotionType->name);
        }

        return redirect('order/updated-receipt/' . $hashId . $campaignTracking);
    }

    /**
     * Display order receipt page
     *
     * Shows order confirmation with details, sends confirmation emails,
     * handles first-order notifications and referral tracking.
     *
     * @param  string  $hashid  Encrypted order ID for security
     * @param  string|null  $campaignTrack  Campaign tracking parameters
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function receipt($hashid, $campaignTrack = null)
    {
        // Find order by encrypted ID for security
        $order = Order::findByEncryptedId($hashid);

        if ($order) {
            $id = $order->id;
            $user = Auth::user();

            // Send confirmation email if not already sent
            if (empty($order->confirmation_email)) {
                // Send first order notification for new customers
                if (($user && $user->orderCount() == 1) || ($order->user && $order->user->orderCount() == 1)) {
                    if (!$user) {
                        $user = $order->user;
                    }
                    $order->mailer()->sendFirstOrderNotification();

                    // Update referral tracking if customer was referred
                    $referralRecord = CustomerReferral::where([
                        'email' => $user->email,
                        'registered' => 1,
                        'order_placed' => 0,
                    ])->first();
                    if ($referralRecord) {
                        $referralData['order_placed'] = 1;
                        $referralRecord->update($referralData);
                    }
                }
                // Check if referrals are allowed in this sales area
                $allowRefer = false;
                if ($user) {
                    $salesAreaId = session()->has('UserDeliveryInformation.alontiDeliveryArea')
                        ? session()->get('UserDeliveryInformation.alontiDeliveryArea.cafe.district_id')
                        : null;
                    if ($salesAreaId) {
                        $salesArea = ReferralSalesArea::where(['sales_area_id' => $salesAreaId])->first();
                        if ($salesArea && $salesArea->available) {
                            $allowRefer = true;
                        }
                    }
                }
                $allowRefer = true; // Override - referrals enabled globally
                // Mark confirmation email as sent and send it
                $order->confirmation_email = 1;
                $order->save();

                // Send order confirmation email to customer
                $order->mailer()->sendOrderConfirmation();
            }
            // Get promotional categories and products for upselling on receipt
            $splOccassionCategory = Category::where(['delivery_exception' => 1, 'parent_id' => null])
                ->with('uniqueurl')
                ->first();
            $soupCategory = Category::where(['name' => 'Soups'])
                ->with(['image', 'uniqueurl'])
                ->first();
            $tingaChickenPowerBowl = Product::where(['name' => 'Tinga Chicken Powerbowl'])
                ->with(['image', 'uniqueurl'])
                ->first();
            // Prepare order details for receipt display
            $payments = Payment::pluck('terms', 'id')->toArray();
            $deliveryTimes = Time::pluck('time', 'id')->toArray();
            $items = $order->cart->items()->withoutAddons()->with('addons')->get();

            // Get delivery area information from session
            $deliveryAreaCount = session()->has('UserDeliveryInformation.alontiDeliveryAreaCount')
                ? session()->get('UserDeliveryInformation.alontiDeliveryAreaCount')
                : 0;
            $deliveryAreaChosen = session()->has('UserDeliveryInformation.deliveryAreaChosen')
                ? session()->get('UserDeliveryInformation.deliveryAreaChosen')
                : false;
            $cafeList = session()->has('UserDeliveryInformation.alontiDeliveryAreaList')
                ? session()->get('UserDeliveryInformation.alontiDeliveryAreaList')
                : [];

            // Get serving ware options selected for this order
            $servingOption = DB::table('offmenus')
                ->join('serving_options', 'serving_options.id', '=', 'offmenus.serving_option_id')
                ->select('serving_options.name', 'serving_options.id', 'offmenus.price', 'offmenus.qty')
                ->where('offmenus.order_id', $order->id)
                ->first();

            return view(
                'receipt',
                compact(
                    'id',
                    'user',
                    'splOccassionCategory',
                    'tingaChickenPowerBowl',
                    'soupCategory',
                    'order',
                    'items',
                    'payments',
                    'deliveryTimes',
                    'deliveryAreaCount',
                    'deliveryAreaChosen',
                    'cafeList',
                    'servingOption'
                )
            );
        } else {
            return redirect('/')->with('notify-failure', 'Not found your order.');
        }
    }

    /**
     * Display updated order receipt page
     *
     * Shows receipt for modified orders and sends order modification notification.
     * Used when orders are changed after initial placement.
     *
     * @param  string  $hashid  Encrypted order ID for security
     * @param  string|null  $campaignTrack  Campaign tracking parameters
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function updatedReceipt($hashid, $campaignTrack = null)
    {
        // Find order by encrypted ID and process if found
        $order = Order::findByEncryptedId($hashid);
        if ($order) {
            $user = Auth::user();
            $id = $order->id;

            // Send order modification notification email
            $order->mailer()->sendOrderModified();
            // Get promotional categories and products for upselling
            $splOccassionCategory = Category::where(['delivery_exception' => 1, 'parent_id' => null])
                ->with('uniqueurl')
                ->first();
            $soupCategory = Category::where(['name' => 'Soups'])
                ->with(['uniqueurl'])
                ->first();
            $tingaChickenPowerBowl = Product::where(['name' => 'Tinga Chicken Powerbowl'])
                ->with(['image', 'uniqueurl'])
                ->first();
            // Check if referrals are allowed in current sales area
            $allowRefer = false;
            if ($user) {
                $salesAreaId = session()->has('UserDeliveryInformation.alontiDeliveryArea')
                    ? session()->get('UserDeliveryInformation.alontiDeliveryArea.cafe.district_id')
                    : null;
                if ($salesAreaId) {
                    $salesArea = ReferralSalesArea::where(['sales_area_id' => $salesAreaId])->first();
                    if ($salesArea && $salesArea->available) {
                        $allowRefer = true;
                    }
                }
            }

            return view(
                'updated-receipt',
                compact('id', 'user', 'splOccassionCategory', 'tingaChickenPowerBowl', 'soupCategory', 'allowRefer')
            );
        } else {
            return redirect('/')->with('notify-failure', 'Not found your order.');
        }
    }
}
