<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\EzCaterOrderPlaced;
use App\Events\EzCaterOrderUpdated;
use App\Http\Controllers\Controller;
use App\Models\Cafe;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\State;
use App\Models\Time;
use App\Models\User;
use App\Models\Zipcode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EzCaterController extends Controller
{
    /**
     * Get the latest webhook log payload for an ezCater order_id.
     * GET /api/ezcater/webhook-log/{order_id}
     */
    public function getWebhookLog(string $orderId)
    {
        $log = DB::table('ezcater_webhook_logs')->where('order_id', $orderId)->orderBy('id', 'desc')->first();

        if (!$log) {
            return response()->json(['message' => 'No data found'], 404);
        }

        $payload = $log->payload;
        if (is_string($payload)) {
            $payload = json_decode($payload, true);
        }

        return response()->json(['data' => $payload]);
    }

    /**
     * Single endpoint to create or update ezCater orders.
     * If ezcater_order_id exists and an order is found -> update.
     * Otherwise create a new order.
     */
    public function handleEzcaterOrder(Request $request)
    {
        Log::info('--------------------- HANDLE EZCATER ORDER ---------------------');

        $data = $request->all();
        $firstOrderEntry = $data[0] ?? null;

        if (!$firstOrderEntry) {
            Log::error('No order data found in payload');

            return response()->json(['status' => false, 'message' => 'Invalid payload'], 400);
        }

        $orderData = $firstOrderEntry['orderData']['orders'][0] ?? null;
        $emailData = $firstOrderEntry['emailData'] ?? null;

        if (!$orderData || !$emailData) {
            Log::error('Order data missing');

            return response()->json(['status' => false, 'message' => 'Invalid order data'], 400);
        }

        $ezcaterOrderId = $emailData['orderId'] ?? null;

        // Determine event type and check for existing order
        $existingOrder = null;
        $eventType = 'accepted';

        // If ezcaterOrderId exists and we have an order -> update
        if ($ezcaterOrderId) {
            $existingOrder = DB::table('orders')->where('ezcater_order_id', $ezcaterOrderId)->first();

            if ($existingOrder) {
                $eventType = 'modified';
            }
        }

        // Prepare webhook log data
        $webhookLogData = [
            'order_id' => $ezcaterOrderId,
            'event_type' => $eventType,
            'payload' => json_encode($data),
            'status' => 'received',
            'error_message' => null,
            'received_at' => now(),
            'processed_at' => null,
            'created_at' => now(),
        ];

        // Log webhook
        try {
            DB::table('ezcater_webhook_logs')->insert($webhookLogData);
        } catch (\Throwable $e) {
            Log::error('Failed to log ezcater webhook: ' . $e->getMessage());
        }

        // Route to appropriate handler
        if ($existingOrder) {
            return $this->updateEzcaterOrder($request, $ezcaterOrderId);
        }

        // Otherwise create new order
        return $this->placeEzcaterOrder($request, $ezcaterOrderId);
    }

    public function placeEzcaterOrder(Request $request, $ezcaterOrderId = null)
    {
        Log::error('--------------------- PLACE ORDER WITH EXACT IDS ---------------------');
        $response = [];
        $data = $request->all();
        $firstOrderEntry = $data[0] ?? null;

        if (!$firstOrderEntry) {
            Log::error('No order data found in payload');

            return response()->json(['status' => false, 'message' => 'Invalid payload'], 400);
        }

        $orderData = $firstOrderEntry['orderData']['orders'][0] ?? null;
        $orderItems = $orderData['items'] ?? [];

        // Check if order items are present
        if (empty($orderItems)) {
            Log::error('Order items are missing');

            return response()->json(['status' => false, 'message' => 'Order items missing'], 400);
        }

        // ✅ 3. Cafe and State Mapping // Cafe info comes from 'store'
        Log::error('✅ 3. Cafe and State Mapping');

        $cafeName = $orderData['store']['name'] ?? null; // or map to actual ID if you store it
        // Find the actual cafe number
        // Extract numeric part using regex
        preg_match('/\d+/', $cafeName, $matches);
        $storeNumber = $matches[0] ?? null;

        // Check if this number exists in cafes table
        $cafeRecord = Cafe::where('cafenum', $storeNumber)->first();

        // Set $cafeId based on existence
        $cafeId = $cafeRecord ? $cafeRecord->id : 200;

        Log::info("Mapped Store #{$storeNumber} → Cafe ID: {$cafeId}");

        $taxableEmail = $cafeRecord->ezcater_profile_email_taxable ?? null;
        $nonTaxableEmail = $cafeRecord->ezcater_profile_email_non_taxable ?? null;
        // Convert salesTaxRemitted to float and check if greater than 0
        $salesTaxRemitted = floatval($orderData['pricing']['salesTaxRemitted'] ?? 0);

        // Choose between taxable and non-taxable email
        $email = $salesTaxRemitted > 0 ? $nonTaxableEmail : $taxableEmail;

        // ✅ 1. Get or create customer
        Log::error('✅ 1. Get or create customer');
        $customer = User::where('email', $email)->first();

        // ✅ 2. Parse delivery time
        Log::error('✅ 2. Parse delivery time');
        $orderTime = $orderData['deliveryTime'] ?? null;

        if (!$orderTime) {
            Log::error('Order deliveryTime missing');

            return response()->json(['status' => false, 'message' => 'Order deliveryTime missing'], 400);
        }

        $timeId = $this->findTimeIdByDeliveryTime($orderTime);

        $stateId = $orderData['store']['state'] ?? null;
        // Find state id by state code
        $state = State::where('code', $stateId)->first();
        $stateId = $state->id ?? null;

        // ✅ 4. Delivery Details // Delivery info
        Log::error('✅ 4. Delivery Details');

        // Check if it's a pickup order (deliveryDetails address is empty)
        $isPickupOrder = empty($orderData['deliveryDetails']['address'] ?? null);

        if ($isPickupOrder) {
            // Use store information for pickup orders
            $deliveryAddress = $orderData['store']['fullAddress'] ?? null;
            $deliveryZip = $orderData['store']['zipCode'] ?? null;
            $deliveryCity = $orderData['store']['city'] ?? null;
            $deliveryState = $orderData['store']['state'] ?? null;
            $deliveryInstructions = $orderData['deliveryDetails']['instructions'] ?? null;
            $receiverName = null;
            $receiverPhone = null;
            $deliveryType = 0; // Pickup
        } else {
            // Use deliveryDetails for delivery orders
            $deliveryAddress = $orderData['deliveryDetails']['fullAddress'] ?? null;
            $deliveryZip = $orderData['deliveryDetails']['zipCode'] ?? null;
            $deliveryCity = $orderData['deliveryDetails']['city'] ?? null;
            $deliveryState = $orderData['deliveryDetails']['state'] ?? null;
            $deliveryInstructions = $orderData['deliveryDetails']['instructions'] ?? null;
            $receiverName = $orderData['deliveryDetails']['contactName'] ?? null;
            $receiverPhone = $orderData['deliveryDetails']['phone'] ?? null;
            $deliveryType = 1; // Delivery
        }

        // Remove timezone (e.g., CDT, IST, etc.)
        $cleanTime = preg_replace('/ [A-Z]{2,4}$/', '', $orderData['deliveryTime']);
        // Parse the date
        $date = Carbon::parse($cleanTime);
        // Convert to Y-m-d format
        $formattedDeliveryDate = $date->format('Y-m-d');

        // ✅ 5. Insert Order
        Log::error('✅ 5. Insert Order');
        $orderDataToInsert = [
            'alonti_user_id' => $customer->id,
            'status' => 'Draft',
            'ordate' => Carbon::now()->format('Y-m-d'),
            'd_date' => $formattedDeliveryDate,
            'time_id' => $timeId,
            'notes' => $deliveryInstructions,
            'cafe_id' => $cafeId,
            'zipcode' => $deliveryZip,
            'd_addr' => $deliveryAddress,
            'is_ezcater_order' => 1,
            'ezcater_order_id' => $ezcaterOrderId ?? null,
            'cancellation_reason' => $orderData['cancellationReason'] ?? null,
            'porder' => $ezcaterOrderId ?? null,
            'payment_id' => 7,
            'deliveryCity' => $deliveryCity,
            'state' => $deliveryState,
            'pdflag' => $deliveryType,
        ];

        DB::beginTransaction();

        try {
            $orderId = DB::table('orders')->insertGetId($orderDataToInsert);

            // ✅ 6. Create Cart
            Log::error('✅ 6. Create Cart');
            $cartId = DB::table('oj_carts')->insertGetId([
                'user_id' => $customer->id,
                'status' => 'Pending',
                'personalized_message' => $deliveryInstructions,
                'cafe_id' => $cafeId,
                'zipcode' => $orderData['store']['zipCode'],
                'state_id' => $stateId,
                'order_id' => $orderId,
                'payment_id' => 7,
            ]);

            Log::info('Created Cart ID: ' . $cartId);

            // ✅ 7. Create Shipping
            Log::error('✅ 7. Create Shipping');
            DB::table('oj_shipping_address')->insert([
                'cart_id' => $cartId,
                'first_name' => $customer->fname,
                'last_name' => $customer->lname,
                'email' => $customer->email,
                'phone_number' => $customer->phone,
                'company_id' => $customer->company_user_id ?? null,
                'industry_id' => $customer->industry_id ?? null,
                'address1' => $deliveryAddress,
                'state' => $stateId,
                'cafe_id' => $cafeId,
                'zipcode' => $deliveryZip,
                'delivery_date' => $formattedDeliveryDate,
                'delivery_time' => $timeId,
                'delivery_instruction' => $deliveryInstructions,
                'receiver_name' => $receiverName,
                'receiver_phone' => $receiverPhone,
                'number_of_members' => $orderData['headcount'] ?? null,
            ]);

            // ✅ 8. Loop through items
            Log::error('✅ 8. Loop through items');
            $taxable = 0;

            foreach ($orderItems as $item) {
                $variantId = $item['variantId'] ?? null;
                $quantity = (int) ($item['quantity'] ?? 1);
                $unitPrice = floatval($item['unitPrice'] ?? 0);
                $itemTotal = $quantity * $unitPrice;

                // Find the variant
                $variant = ProductVariant::with('product')->find($variantId);

                // 🟢 If variantId is null, treat as off-menu
                if (!$variantId || !$variant) {
                    $cartOffmenuId = DB::table('offmenus')->insertGetId([
                        'order_id' => $orderId,
                        'comments' => $item['name'] ?? 'Offmenu Item',
                        'price' => floatval($item['unitPrice'] ?? 0),
                        'qty' => $item['quantity'] ?? 1,
                        'txbl' => 1,
                        'flag' => 1,
                        'offmenu_credit_id' => null,
                        'coupon_id' => null,
                        'vendor' => null,
                        'discount' => null,
                        'free_delivery' => 0,
                        'serving_option_id' => null,
                        'cart_id' => $cartId,
                    ]);
                    $taxable += $itemTotal;

                    $specialInstructionsForOffmenu = '';

                    // Insert item options
                    if (!empty($item['options'])) {
                        foreach ($item['options'] as $option) {
                            foreach ($option['selections'] as $selection) {
                                $selectionName = $selection['name'] ?? null;
                                $specialInstructionsForOffmenu .= "{$selectionName}, ";
                            }
                        }
                    }

                    // Update oj_cart_items with special instructions for missing options
                    if (!empty($specialInstructionsForOffmenu)) {
                        $existingComments = DB::table('offmenus')->where('id', $cartOffmenuId)->value('comments');
                        $updatedComment = trim(
                            $existingComments . '. ezCater only option: ' . $specialInstructionsForOffmenu
                        );

                        // Remove last comma
                        $updatedComment = rtrim($updatedComment, ', ');

                        DB::table('offmenus')
                            ->where('id', $cartOffmenuId)
                            ->update([
                                'comments' => $updatedComment,
                            ]);
                        // Log the update
                        Log::info(
                            "Updated special instructions for Offmenu Item ID: {$cartOffmenuId} with missing options."
                        );
                    }

                    continue;
                }

                // Find product with $variant->product_id
                $product = Product::find($variant->product_id);

                // Log $variant
                Log::info('Processing item', [
                    'item_name' => $item['name'] ?? null,
                    'variantId' => $variantId,
                    'found_variant' => $variant,
                ]);

                $productId = $product->id;
                $categoryId = $product->category_id ?? null;

                // ✅ Insert cart item
                $cartItemId = DB::table('oj_cart_items')->insertGetId([
                    'cart_id' => $cartId,
                    'product_id' => $productId,
                    'product_name' => $item['name'],
                    'category_id' => $categoryId,
                    'product_variant_id' => $variantId,
                    'quantity' => $quantity,
                    'unit_price' => floatval($item['unitPrice'] ?? 0),
                    'state_price_id' => 1, // TODO: Fetch actual state_price_id later
                    'total' => $itemTotal,
                    'special_instruction' => $item['specialInstructions'] ?? null,
                ]);

                $taxable += $itemTotal;

                // Insert options if available
                // if (isset($item['options']) && is_array($item['options']) && $cartItemId) {

                $specialInstructionsForMissingOptions = '';

                if (!empty($item['options'])) {
                    foreach ($item['options'] as $option) {
                        $optionId = $option['id'] ?? null;
                        $optionName = $option['name'] ?? null;

                        foreach ($option['selections'] as $selection) {
                            $selectionId = $selection['id'] ?? null;
                            $selectionName = $selection['name'] ?? null;

                            if ($selectionId === null) {
                                $specialInstructionsForMissingOptions .= "'{$selectionName}' ";
                                Log::warning(
                                    "Option '{$selectionName}' has a selection with missing ID for Order ID: {$orderId}"
                                );

                                continue; // Skip this selection
                            }

                            $selectionPrice = floatval($selection['price'] ?? 0);
                            $selectionUnitPrice = floatval($selection['unitPrice'] ?? 0);

                            DB::table('oj_cart_options')->insert([
                                'cart_item_id' => $cartItemId,
                                'product_option_id' => $optionId,
                                'product_selection_id' => $selectionId,
                                'name' => $optionName,
                                'unit_price' => $selectionUnitPrice,
                                'quantity' => $selection['quantity'],
                                'total' => $selectionPrice,
                                'state_price_id' => 1, // TODO: Fetch actual state_price_id later
                                'is_free' => 1,
                            ]);

                            $taxable += $selectionPrice;
                        }
                    }
                }

                // Update oj_cart_items with special instructions for missing options
                if (!empty($specialInstructionsForMissingOptions)) {
                    $existingInstructions = DB::table('oj_cart_items')
                        ->where('id', $cartItemId)
                        ->value('special_instruction');
                    $updatedInstructions = trim(
                        $existingInstructions . ' ezCater only option: ' . $specialInstructionsForMissingOptions
                    );
                    DB::table('oj_cart_items')
                        ->where('id', $cartItemId)
                        ->update([
                            'special_instruction' => $updatedInstructions,
                        ]);
                    // Log the update
                    Log::info("Updated special instructions for Cart Item ID: {$cartItemId} with missing options.");
                }
            } // End of saving items foreach loop

            // ✅ 9. Apply ezRewardsPromo
            Log::error('✅ 9. Apply ezRewardsPromo');
            $ezRewardsPromo = 0;

            if (isset($orderData['pricing']['alontiManagerComp'])) {
                // Remove $ sign and convert to float
                $ezRewardsPromo = floatval(str_replace('$', '', $orderData['pricing']['alontiManagerComp']));

                // Insert offmenu value into order data
                DB::table('offmenus')->insert([
                    'order_id' => $orderId,
                    'comments' => 'ezRewards promo applied via ezCater automation',
                    'price' => $ezRewardsPromo,
                    'qty' => 1,
                    'txbl' => 1,
                    'flag' => 2,
                    'offmenu_credit_id' => 10,
                ]);

                $taxable += $ezRewardsPromo;
            }

            $deliveryFee = 0;

            if (isset($orderData['pricing']['alontiDeliveryFee'])) {
                $deliveryFee = $orderData['pricing']['alontiDeliveryFee'];
                // Convert $deliveryFee from string to float
                $deliveryFee = floatval(str_replace('$', '', $deliveryFee));
            }

            // $grandTotal = $taxable + $deliveryFee;

            // Update totals
            DB::table('oj_carts')
                ->where('id', $cartId)
                ->update([
                    'subtotal' => $taxable,
                    'taxable' => $taxable,
                    'delivery_fee' => $deliveryFee,
                    'total' => $orderData['totalAmount'],
                    'gratuity' => $orderData['pricing']['tip'],
                ]);

            $salesAreaId = $this->salesAreaBasedOnZipCode($orderId, $deliveryZip);

            // Check if state is CA
            if ($orderData['store']['state'] === 'CA') {
                DB::table('orders')
                    ->where('id', $orderId)
                    ->update([
                        'salestax' => $orderData['pricing']['salesTax'],
                        'taxable' => $orderData['pricing']['alontiTaxable'],
                        'delivery' => $orderData['pricing']['alontiDeliveryFee'],
                        'total' => $orderData['totalAmount'],
                        'gratuity' => $orderData['pricing']['tip'],
                        'sales_area_id' => $salesAreaId,
                    ]);
            } else {
                DB::table('orders')
                    ->where('id', $orderId)
                    ->update([
                        'total' => $orderData['totalAmount'],
                        'gratuity' => $orderData['pricing']['tip'],
                        'delivery' => $deliveryFee,
                        'taxable' => $taxable,
                        'sales_area_id' => $salesAreaId,
                    ]);
            }

            // Commit the transaction
            DB::commit();

            // Dispatch event to notify managers
            // event(new EzCaterOrderPlaced($orderId, $ezcaterOrderId));

            $response[] = [
                'status' => true,
                'message' => 'Order placed successfully.',
                'data' => [
                    'order_id' => $orderId,
                    'ezcater_order_id' => $ezcaterOrderId ?? null,
                    'encryptedOrderId' => urlencode(app('hashid')->encode($orderId)),
                ],
            ];

            return response()->json($response);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Order placement failed: ' . $e->getMessage());

            return response()->json(
                ['status' => false, 'message' => 'Error placing order', 'error' => $e->getMessage()],
                500
            );
        }
    }

    public function updateEzcaterOrder(Request $request, $ezcaterOrderId = null)
    {
        Log::info('--------------------- UPDATE EZCATER ORDER EDITED ---------------------');

        $data = $request->all();
        $firstOrderEntry = $data[0] ?? null;

        if (!$firstOrderEntry) {
            Log::error('No order data found in payload');

            return response()->json(['status' => false, 'message' => 'Invalid payload'], 400);
        }

        $orderData = $firstOrderEntry['orderData']['orders'][0] ?? null;

        if (!$orderData || empty($orderData['items'])) {
            Log::error('Order data or items missing');

            return response()->json(['status' => false, 'message' => 'Invalid order data or missing items'], 400);
        }

        if (!$ezcaterOrderId) {
            Log::error('Missing ezcater_order_id');

            return response()->json(['status' => false, 'message' => 'Missing ezcater_order_id'], 400);
        }

        $existingOrder = DB::table('orders')->where('ezcater_order_id', $ezcaterOrderId)->first();

        if (!$existingOrder) {
            Log::info("Order not found for ezcater_order_id: {$ezcaterOrderId}. Creating new order instead.");

            return $this->placeEzcaterOrder($request);
        }

        DB::beginTransaction();

        try {
            $orderId = $existingOrder->id;
            Log::info("Updating existing order ID: {$orderId}");

            /* -------------------------
             *  CAFÉ + STATE MAPPING
             * ------------------------- */

            // Extract Store Number once
            $storeName = $orderData['store']['name'] ?? null;
            preg_match('/\d+/', $storeName, $matches);
            $storeNumber = $matches[0] ?? null;

            // Lookup cafe once
            $cafeRecord = Cafe::where('cafenum', $storeNumber)->first();
            $cafeId = $cafeRecord ? $cafeRecord->id : 200;

            Log::info("Mapped Store #{$storeNumber} → Cafe ID: {$cafeId}");

            // Choose taxable vs non-taxable email
            $taxableEmail = $cafeRecord->ezcater_profile_email_taxable ?? null;
            $nonTaxableEmail = $cafeRecord->ezcater_profile_email_non_taxable ?? null;
            $salesTaxRemitted = floatval($orderData['pricing']['salesTaxRemitted'] ?? 0);
            $email = $salesTaxRemitted > 0 ? $nonTaxableEmail : $taxableEmail;

            $customer = User::where('email', $email)->first();

            // Clean delivery time → Y-m-d
            $cleanTime = preg_replace('/ [A-Z]{2,4}$/', '', $orderData['deliveryTime']);
            $deliveryDate = Carbon::parse($cleanTime)->format('Y-m-d');

            // Get time slot ID
            $timeId = $this->findTimeIdByDeliveryTime($orderData['deliveryTime']);

            // State mapping
            $stateCode = $orderData['store']['state'] ?? null;
            $state = State::where('code', $stateCode)->first();
            $stateId = $state->id ?? null;

            // Check if it's a pickup order (deliveryDetails address is empty)
            $isPickupOrder = empty($orderData['deliveryDetails']['address'] ?? null);

            if ($isPickupOrder) {
                // Use store information for pickup orders
                $deliveryAddress = $orderData['store']['fullAddress'] ?? null;
                $deliveryZip = $orderData['store']['zipCode'] ?? null;
                $deliveryCity = $orderData['store']['city'] ?? null;
                $deliveryState = $orderData['store']['state'] ?? null;
                $deliveryInstructions = $orderData['deliveryDetails']['instructions'] ?? null;
                $receiverName = null;
                $receiverPhone = null;
                $deliveryType = 0; // Pickup
            } else {
                // Use deliveryDetails for delivery orders
                $deliveryAddress = $orderData['deliveryDetails']['fullAddress'] ?? null;
                $deliveryZip = $orderData['deliveryDetails']['zipCode'] ?? null;
                $deliveryCity = $orderData['deliveryDetails']['city'] ?? null;
                $deliveryState = $orderData['deliveryDetails']['state'] ?? null;
                $deliveryInstructions = $orderData['deliveryDetails']['instructions'] ?? null;
                $receiverName = $orderData['deliveryDetails']['contactName'] ?? null;
                $receiverPhone = $orderData['deliveryDetails']['phone'] ?? null;
                $deliveryType = 1; // Delivery
            }

            /* -------------------------
             * UPDATE ORDER BASIC FIELDS
             * ------------------------- */
            $salesAreaId = $this->salesAreaBasedOnZipCode($orderId, $deliveryZip);

            /* ---------------------------------------
             * DELETE OLD CART DATA (NO DUPLICATE CODE)
             * --------------------------------------- */

            $cartIds = DB::table('oj_carts')->where('order_id', $orderId)->pluck('id');
            $cartItemIds = DB::table('oj_cart_items')->whereIn('cart_id', $cartIds)->pluck('id');

            if ($cartItemIds->isNotEmpty()) {
                DB::table('oj_cart_options')->whereIn('cart_item_id', $cartItemIds)->delete();
            }

            if ($cartIds->isNotEmpty()) {
                DB::table('oj_cart_items')->whereIn('cart_id', $cartIds)->delete();
                DB::table('oj_shipping_address')->whereIn('cart_id', $cartIds)->delete();
                DB::table('offmenus')->where('order_id', $orderId)->delete();
                DB::table('oj_carts')->whereIn('id', $cartIds)->delete();
            }

            Log::info("Previous linked records deleted for order ID: {$orderId}");

            DB::table('orders')
                ->where('id', $orderId)
                ->update([
                    'alonti_user_id' => $customer->id,
                    'status' => 'Draft',
                    'ordate' => Carbon::now()->format('Y-m-d'),
                    'd_date' => $deliveryDate,
                    'time_id' => $timeId,
                    'notes' => $deliveryInstructions,
                    'cafe_id' => $cafeId,
                    'zipcode' => $orderData['deliveryDetails']['zipCode'] ?? null,
                    'd_addr' => $deliveryAddress,
                    'cancellation_reason' => $orderData['cancellationReason'] ?? null,
                    'last_updated' => now(),
                    'porder' => $ezcaterOrderId ?? null,
                    'payment_id' => 7,
                    'deliveryCity' => $deliveryCity,
                    'state' => $deliveryState,
                    'sales_area_id' => $salesAreaId,
                    'pdflag' => $deliveryType,
                ]);

            /* -------------------------
             * CREATE NEW CART
             * ------------------------- */

            $cartId = DB::table('oj_carts')->insertGetId([
                'user_id' => $customer->id,
                'status' => 'Pending',
                'personalized_message' => $deliveryInstructions,
                'cafe_id' => $cafeId,
                'zipcode' => $orderData['store']['zipCode'] ?? null,
                'state_id' => $stateId,
                'order_id' => $orderId,
                'payment_id' => 7,
            ]);

            DB::table('oj_shipping_address')->insert([
                'cart_id' => $cartId,
                'first_name' => $customer->fname,
                'last_name' => $customer->lname,
                'email' => $customer->email,
                'phone_number' => $customer->phone,
                'company_id' => $customer->company_user_id ?? null,
                'industry_id' => $customer->industry_id ?? null,
                'address1' => $deliveryAddress,
                'state' => $stateId,
                'cafe_id' => $cafeId,
                'zipcode' => $deliveryZip,
                'delivery_date' => $deliveryDate,
                'delivery_time' => $timeId,
                'delivery_instruction' => $deliveryInstructions,
                'receiver_name' => $receiverName,
                'receiver_phone' => $receiverPhone,
                'number_of_members' => $orderData['headcount'] ?? null,
            ]);

            /* -------------------------
             * ADD ITEMS
             * ------------------------- */

            $taxable = 0;

            foreach ($orderData['items'] as $item) {
                $variantId = $item['variantId'] ?? null;
                $quantity = (int) ($item['quantity'] ?? 1);
                $unitPrice = floatval($item['unitPrice'] ?? 0);
                $itemTotal = $quantity * $unitPrice;

                $variant = $variantId ? ProductVariant::with('product')->find($variantId) : null;

                if (!$variant) {
                    $cartOffmenuId = DB::table('offmenus')->insertGetId([
                        'order_id' => $orderId,
                        'comments' => $item['name'] ?? 'Offmenu Item',
                        'price' => $unitPrice,
                        'qty' => $quantity,
                        'txbl' => 1,
                        'flag' => 1,
                        'cart_id' => $cartId,
                    ]);

                    $taxable += $itemTotal;

                    // Insert item options
                    if (!empty($item['options'])) {
                        $specialInstructionsForOffmenu = '';

                        foreach ($item['options'] as $option) {
                            foreach ($option['selections'] as $selection) {
                                $selectionName = $selection['name'] ?? null;
                                $specialInstructionsForOffmenu .= "{$selectionName}, ";
                            }
                        }

                        // Update oj_cart_items with special instructions for missing options
                        if (!empty($specialInstructionsForOffmenu)) {
                            $existingComments = DB::table('offmenus')->where('id', $cartOffmenuId)->value('comments');
                            $updatedComment = trim(
                                $existingComments . '. ezCater only option: ' . $specialInstructionsForOffmenu
                            );

                            // Remove last comma
                            $updatedComment = rtrim($updatedComment, ', ');

                            DB::table('offmenus')
                                ->where('id', $cartOffmenuId)
                                ->update([
                                    'comments' => $updatedComment,
                                ]);
                            // Log the update
                            Log::info(
                                "Updated special instructions for Offmenu Item ID: {$cartOffmenuId} with missing options."
                            );
                        }
                    }

                    continue;
                }

                $product = $variant ? Product::find($variant->product_id) : null;

                $cartItemId = DB::table('oj_cart_items')->insertGetId([
                    'cart_id' => $cartId,
                    'product_id' => $product->id,
                    'product_name' => $item['name'],
                    'category_id' => $product->category_id,
                    'product_variant_id' => $variantId,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'state_price_id' => 1,
                    'total' => $itemTotal,
                    'special_instruction' => $item['specialInstructions'] ?? null,
                ]);

                $taxable += $itemTotal;

                $specialInstructionsForMissingOptions = '';

                // Insert item options
                if (!empty($item['options'])) {
                    foreach ($item['options'] as $option) {
                        foreach ($option['selections'] as $selection) {
                            DB::table('oj_cart_options')->insert([
                                'cart_item_id' => $cartItemId,
                                'product_option_id' => $option['id'] ?? null,
                                'product_selection_id' => $selection['id'] ?? null,
                                'name' => $option['name'] ?? null,
                                'unit_price' => floatval($selection['unitPrice'] ?? 0),
                                'quantity' => $selection['quantity'] ?? 1,
                                'total' => floatval($selection['price'] ?? 0),
                                'state_price_id' => 1,
                                'is_free' => 1,
                            ]);
                        }
                    }
                }
            }

            /* -------------------------
             * APPLY PROMOS + TOTALS
             * ------------------------- */
            if (isset($orderData['pricing']['alontiManagerComp'])) {
                // Remove $ sign and convert to float
                $ezRewardsPromo = floatval(str_replace('$', '', $orderData['pricing']['alontiManagerComp']));

                // Insert offmenu value into order data
                DB::table('offmenus')->insert([
                    'order_id' => $orderId,
                    'comments' => 'ezRewards promo reapplied via update',
                    'price' => $ezRewardsPromo,
                    'qty' => 1,
                    'txbl' => 1,
                    'flag' => 2,
                    'offmenu_credit_id' => 10,
                ]);

                $taxable += $ezRewardsPromo;
            }

            $deliveryFee = floatval(str_replace('$', '', $orderData['pricing']['alontiDeliveryFee'] ?? 0));
            // $grandTotal = $taxable + $deliveryFee;

            DB::table('oj_carts')
                ->where('id', $cartId)
                ->update([
                    'subtotal' => $taxable,
                    'taxable' => $taxable,
                    'delivery_fee' => $deliveryFee,
                    'total' => $orderData['totalAmount'],
                    'gratuity' => $orderData['pricing']['tip'] ?? 0,
                ]);

            /* -------------------------
             * FINAL ORDER UPDATE
             * ------------------------- */

            if ($orderData['store']['state'] === 'CA') {
                DB::table('orders')
                    ->where('id', $orderId)
                    ->update([
                        'total' => $orderData['totalAmount'],
                        'delivery' => $orderData['pricing']['alontiDeliveryFee'],
                        'taxable' => $orderData['pricing']['alontiTaxable'],
                        'salestax' => $orderData['pricing']['salesTax'],
                        'gratuity' => $orderData['pricing']['tip'] ?? 0,
                        'status' => 'Ordered',
                    ]);
            } else {
                DB::table('orders')
                    ->where('id', $orderId)
                    ->update([
                        'total' => $orderData['totalAmount'],
                        'delivery' => $deliveryFee,
                        'taxable' => $taxable,
                        'gratuity' => $orderData['pricing']['tip'] ?? 0,
                        'status' => 'Ordered',
                    ]);
            }

            DB::commit();

            // Dispatch event to notify managers
            // event(new EzCaterOrderUpdated($orderId, $ezcaterOrderId));

            Log::info("Order successfully updated: {$orderId}");

            return response()->json([
                'status' => true,
                'message' => 'Order updated successfully.',
                'data' => [
                    'order_id' => $orderId,
                    'ezcater_order_id' => $ezcaterOrderId,
                    'encryptedOrderId' => urlencode(app('hashid')->encode($orderId)),
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Order update failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(
                [
                    'status' => false,
                    'message' => 'Error updating order',
                    'error' => $e->getMessage(),
                ],
                500
            );
        }
    }

    /**
     * Find the time slot ID based on the order time
     *
     * @param  string  $orderTime  The delivery time string
     * @return int The time slot ID (defaults to 1 if not found)
     */
    private function findTimeIdByDeliveryTime(string $orderTime): int
    {
        $normalized = Carbon::parse($orderTime)->format('g:i A');

        $timeId = Time::whereRaw(
            "
            STR_TO_DATE(?, '%l:%i %p')
            >=
                STR_TO_DATE(SUBSTRING_INDEX(`time`, ' - ', 1), '%l:%i %p')
            AND
            STR_TO_DATE(?, '%l:%i %p')
            <
                STR_TO_DATE(SUBSTRING_INDEX(`time`, ' - ', -1), '%l:%i %p')
        ",
            [$normalized, $normalized]
        )
            ->orderBy('sort', 'asc')
            ->value('id');

        return $timeId ?: 1; // Default to 1 if not found
    }

    private function salesAreaBasedOnZipCode($orderId, $zipCode)
    {
        if ($zipCode) {
            $zipcode = Zipcode::where([
                'zipcode' => $zipCode,
                'status' => 1,
            ])->first();

            // If zipcode exists and has an associated district, use it
            if (!empty($zipcode) && isset($zipcode->district_id)) {
                return $zipcode->district_id;
            }
        }

        $cafeWithOrder = Cafe::find()
            ->matching('Orders', function ($q) use ($orderId) {
                return $q->where(['Orders.id' => $orderId]);
            })
            ->first();

        return $cafeWithOrder->district_id;
    }

    public function cancelOrder(Request $request)
    {
        // Inject HTTP Request
        // Logic to cancel the order
        $ezCaterOrderId = $request->input('ezCaterOrderId');
        $status = $request->input('status');
        $reason = $request->input('reason');

        // Validate inputs
        if (!$ezCaterOrderId || !$status) {
            Log::error('Invalid request data', ['request' => $request->all()]);

            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Invalid request data',
                ],
                400
            );
        }

        // Find the order with the given ezCaterOrderId
        $order = Order::where('ezcater_order_id', $ezCaterOrderId)->first();

        if (!$order) {
            Log::error('Order not found for ezCaterOrderId: ' . $ezCaterOrderId);

            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Order not found',
                ],
                404
            );
        }

        if ($status !== 'Cancelled') {
            Log::error('Invalid status for cancellation: ' . $status);

            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Invalid status for cancellation',
                ],
                400
            );
        }

        // Check if the order is already cancelled
        if ($order->status === 'Canceled') {
            Log::warning('Order already cancelled', ['order_id' => $order->id]);

            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Order already cancelled',
                ],
                200
            );
        }

        try {
            // Update order status to cancelled
            $order->status = 'Canceled';
            $order->cancellation_reason = $reason;
            $order->save();

            // Send an email notification to the manager $order->cafe->director->email
            Mail::raw("The ezCater order with ID {$ezCaterOrderId} has been cancelled.\nReason: {$reason}", function (
                $message
            ) use ($order) {
                $message->to($order->cafe->director->email)->subject("ezCater Order Cancelled: {$order->id}");
            });
        } catch (\Throwable $th) {
            Log::error('Error cancelling order', ['order_id' => $order->id, 'error' => $th->getMessage()]);

            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Error cancelling order',
                ],
                500
            );
        }

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Order cancelled successfully',
            ],
            200
        );
    }
}
