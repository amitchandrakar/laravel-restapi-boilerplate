<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Alonti\Cart\CartManager;
use App\Alonti\Invitation\InvitationManager;
use App\Alonti\ZipManager\ZipManager;
use App\Http\Resources\Api\V1\ProductBoxLunchDetailResource;
use App\Http\Resources\Api\V1\ProductDetailResource;
use App\Http\Resources\Api\V1\ProductIndexResource;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Dietary;
use App\Models\GroupOrder;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\UniqueUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(ZipManager $zipManager, $name, Request $request)
    {
        // $this->checkQueueDbConnection();

        $dietary = isset($request->dietary) ? $request->dietary : 0;
        $requestFromInvitee = config()->get('app.request-from-invitee');
        $budget = 0;
        $invitee_total = 0;
        $goConfigExist = false;
        $budgetActive = false;

        if ($requestFromInvitee) {
            $cartInfo = app(CartManager::class)->getActiveCart();
            $items = $cartInfo->items();
            $items = $items->where('invitee_id', session()->get('invitation.invitee_id'));
            $leader = app(InvitationManager::class)->getLeader();
            $group = GroupOrder::find(session()->get('invitation.group_order_id'));
            $invitee_total = round($cartInfo->totalForInvitee(session()->get('invitation.invitee_id')), 2);

            if ($cartInfo->groupOrderConfig) {
                if ($cartInfo->groupOrderConfig->invitee_budget > 0) {
                    $budget = $cartInfo->groupOrderConfig->invitee_budget;
                    $budgetActive = true;
                }

                $goConfigExist = $cartInfo->groupOrderConfig ? true : false;
            }
        }

        $categories = Category::getCategories($requestFromInvitee);

        $stateId = $zipManager->getDeliveryZipcodeStateId();
        $url = UniqueUrl::getUniqueUrlByUrl($name);

        $deliveryAreaCount = session()->has('UserDeliveryInformation.alontiDeliveryAreaCount')
            ? session()->get('UserDeliveryInformation.alontiDeliveryAreaCount')
            : 0;

        $deliveryAreaChosen = session()->has('UserDeliveryInformation.deliveryAreaChosen')
            ? session()->get('UserDeliveryInformation.deliveryAreaChosen')
            : false;

        $cafeList = session()->has('UserDeliveryInformation.alontiDeliveryAreaList')
            ? session()->get('UserDeliveryInformation.alontiDeliveryAreaList')
            : [];

        $deliveryAreaInfo = session()->has('UserDeliveryInformation') ? session()->get('UserDeliveryInformation') : [];

        $cafeName = isset($deliveryAreaInfo['alontiDeliveryArea']['cafe']['cafename'])
            ? $deliveryAreaInfo['alontiDeliveryArea']['cafe']['cafename']
            : '';

        if ($url->isCategory()) {
            // Get givenZipCode from object $zipManager
            $givenZipCode = $zipManager->givenZipCode;
            $cacheKey = 'products.index.' . $stateId . '.' . $url->url . '.' . $givenZipCode;

            // Check if product cache exists
            if (Cache::has($cacheKey)) {
                $compact = Cache::get($cacheKey);

                $compact['category']['subCategories'] = $this->filterCategoriesForInvites(
                    $compact['category']['subCategories'],
                    $requestFromInvitee
                );

                return $this->successResponse(
                    ProductIndexResource::make([
                        'category' => $compact['category'],
                        'delivery_area_count' => $compact['deliveryAreaCount'] ?? null,
                        'delivery_area_chosen' => $compact['deliveryAreaChosen'] ?? null,
                        'cafe_list' => $compact['cafeList'] ?? null,
                        'dietary' => $compact['dietary'] ?? null,
                        'budget' => $compact['budget'] ?? null,
                        'invitee_total' => $compact['invitee_total'] ?? null,
                        'request_from_invitee' => $compact['requestFromInvitee'] ?? null,
                        'go_config_exist' => $compact['goConfigExist'] ?? null,
                        'budget_active' => $compact['budgetActive'] ?? null,
                        'dietaries' => $compact['dietaries'],
                        'url' => $compact['url'] ?? null,
                    ]),
                    'Success'
                );
            }

            $category = Category::getCategoryAndProductList($url->entity_id, $stateId, $requestFromInvitee);

            $dietaries = Dietary::getDietaries();
            $subCategories = $category->subCategories;

            $compact = compact(
                'category',
                'deliveryAreaCount',
                'deliveryAreaChosen',
                'cafeList',
                'dietary',
                'budget',
                'invitee_total',
                'requestFromInvitee',
                'goConfigExist',
                'budgetActive',
                'dietaries',
                'url'
            );

            // Put products in cache
            Cache::put($cacheKey, $compact);
            $compact['category']['subCategories'] = $this->filterCategoriesForInvites(
                $compact['category']['subCategories'],
                $requestFromInvitee
            );

            if (!$category || ($category->subCategories->count() == 0 && $category->products->count() == 0)) {
                return $this->errorResponse('These products are not available for cafe ' . $cafeName, 400);
            }

            return $this->successResponse(
                ProductIndexResource::make([
                    'category' => $category,
                    'delivery_area_count' => $deliveryAreaCount,
                    'delivery_area_chosen' => $deliveryAreaChosen,
                    'cafe_list' => $cafeList,
                    'dietary' => $dietary,
                    'budget' => $budget,
                    'invitee_total' => $invitee_total,
                    'request_from_invitee' => $requestFromInvitee,
                    'go_config_exist' => $goConfigExist,
                    'budget_active' => $budgetActive,
                    'dietaries' => $dietaries,
                    'url' => $url,
                ]),
                'Success'
            );
        }

        $item = '';
        $cartItems = [];

        if ($url->isProduct()) {
            $product = $this->getProductDetail($url->entity_id, $stateId, $request);

            if ($request->has('item_id')) {
                $item = CartItem::getCartItemById($request->get('item_id'), $product->id);

                if (!$item) {
                    abort(404);
                } else {
                    $input = $this->getCartItemInput($item);
                    $cartInfo = app(CartManager::class)->getActiveCart();
                    $itemAvailability = app(CartManager::class)->itemAvailability($cartInfo, $input);

                    if (!$itemAvailability['status']) {
                        return $this->errorResponse(
                            $itemAvailability['msg'] .
                                ', please delete the unavailable product and choose from the available products and proceed.',
                            400
                        );
                    }
                }
            }

            $requestFromInvitee = config()->get('app.request-from-invitee');
            $budget = 0;
            $invitee_total = 0;
            $otherItemTotalForInvitee = 0;
            $editItem = false;
            $goConfigExist = false;
            $budgetActive = false;
            $inviteeName = '';

            if ($requestFromInvitee) {
                $cartInfo = app(CartManager::class)->getActiveCart();
                $itemId = $item ? $item->id : null;
                $editItem = $item ? true : false;
                $otherItemTotalForInvitee = round(
                    $cartInfo->totalForInvitee(session()->get('invitation.invitee_id'), $itemId),
                    2
                );
                $items = $cartInfo->items();
                $items = $items->where('invitee_id', session()->get('invitation.invitee_id'));
                $leader = app(InvitationManager::class)->getLeader();
                $group = GroupOrder::find(session()->get('invitation.group_order_id'));
                $invitee_total = round($cartInfo->totalForInvitee(session()->get('invitation.invitee_id')), 2);

                if ($cartInfo->groupOrderConfig) {
                    if ($cartInfo->groupOrderConfig->invitee_budget > 0) {
                        $budget = $cartInfo->groupOrderConfig->invitee_budget;
                        $budgetActive = true;
                    }
                    $goConfigExist = $cartInfo->groupOrderConfig ? true : false;
                }

                $inviteeName = session()->get('invitation.invitee_name');
            }

            if ($product->category && $product->category->type == Category::BULK && !$requestFromInvitee) {
                $cartItems = CartItem::getBoxLunchItems();
                $products = $this->getBoxLunchProducts($product->category->id, $stateId);

                return $this->successResponse(
                    ProductBoxLunchDetailResource::make([
                        'product' => $product,
                        'products' => $products,
                        'cart_items' => $cartItems,
                        'item' => $item,
                        'request_from_invitee' => $requestFromInvitee,
                        'delivery_area_count' => $deliveryAreaCount,
                        'delivery_area_chosen' => $deliveryAreaChosen,
                        'cafe_list' => $cafeList,
                        'budget' => $budget,
                        'other_item_total_for_invitee' => $otherItemTotalForInvitee,
                        'edit_item' => $editItem,
                        'invitee_total' => $invitee_total,
                        'go_config_exist' => $goConfigExist,
                        'invitee_name' => $inviteeName,
                        'budget_active' => $budgetActive,
                        'url' => $url,
                    ]),
                    'Success'
                );
            }

            return $this->successResponse(
                ProductDetailResource::make([
                    'product' => $product,
                    'item' => $item,
                    'request_from_invitee' => $requestFromInvitee,
                    'delivery_area_count' => $deliveryAreaCount,
                    'delivery_area_chosen' => $deliveryAreaChosen,
                    'cafe_list' => $cafeList,
                    'budget' => $budget,
                    'other_item_total_for_invitee' => $otherItemTotalForInvitee,
                    'edit_item' => $editItem,
                    'invitee_total' => $invitee_total,
                    'go_config_exist' => $goConfigExist,
                    'invitee_name' => $inviteeName,
                    'budget_active' => $budgetActive,
                    'url' => $url,
                ]),
                'Success'
            );
        }
    }

    private function filterCategoriesForInvites($sub_categories, $requestFromInvitee)
    {
        if (!$requestFromInvitee) {
            return $sub_categories;
        }

        $subCategories = [];

        foreach ($sub_categories as $sub) {
            if ($sub['visible_to_invitee']) {
                $subCategories[] = $sub;
            }
        }

        return collect($subCategories);
    }

    private function getProductDetail($id, $stateId, $request)
    {
        $product = Product::getProductById($id, $stateId);

        if ($request->has('package')) {
            $product->selected_package_id = ProductVariant::getPackageIdByName($request->get('package'), $product->id);
        }

        return $product;
    }

    private function getBoxLunchProducts($categoryId, $stateId)
    {
        return Product::getProductsByCategororyId($categoryId, $stateId);
    }

    public function getCartItemInput($item)
    {
        $input = [];
        $input['product_id'] = $item->product_id;
        $input['product_variant_id'] = $item->product_variant_id;
        $input['cartOptions'] = [];

        if (!empty($item->options)) {
            $i = 0;

            foreach ($item->options as $key => $value) {
                $input['cartOptions'][$i]['product_option_id'] = $value->product_option_id;
                $i++;
            }
        }

        if (!empty($item->addons)) {
            $i = 0;

            foreach ($item->addons as $key => $value) {
                $input['addons'][$i]['product_id'] = $value->product_id;
                $input['addons'][$i]['product_variant_id'] = $value->product_variant_id;
                $input['addons'][$i]['cartOptions'] = [];
                $j = 0;

                foreach ($value->options as $k => $v) {
                    $input['addons'][$i]['cartOptions'][$j]['product_option_id'] = $v->product_option_id;
                    $j++;
                }
                $i++;
            }
        }

        return $input;
    }

    public function filterCategoryProducts($category, $budget)
    {
        if ($category->subCategories->count() > 0 || $category->products->count() > 0) {
            if ($category->subCategories->count() > 0) {
                foreach ($category->subCategories as $subCatKey => $subCatValue) {
                    if ($subCatValue->products->count() > 0) {
                        foreach ($subCatValue->products as $prodKey => $prodValue) {
                            if (
                                $prodValue->include_product_variant != 1 &&
                                (isset($prodValue->price) && !((float) $budget >= (float) $prodValue->price))
                            ) {
                                $category->subCategories[$subCatKey]->products->splice($prodKey, 1);
                            }
                        }
                    } elseif ($subCatValue->subCategories->count() > 0) {
                        foreach ($subCatValue->subCategories as $subSubCatKey => $subSubCatValue) {
                            if ($subSubCatValue->products->count() > 0) {
                                foreach ($subSubCatValue->products as $prodKey => $prodValue) {
                                    if (
                                        $prodValue->include_product_variant != 1 &&
                                        (isset($prodValue->price) && !((float) $budget >= (float) $prodValue->price))
                                    ) {
                                        $category->subCategories[$subCatKey]->subCategories[
                                            $subSubCatKey
                                        ]->products->splice($prodKey, 1);
                                    }
                                }
                            }
                        }
                    }
                }
            } elseif ($category->products->count() > 0) {
                foreach ($category->products as $prodKey => $prodValue) {
                    if (
                        $prodValue->include_product_variant != 1 &&
                        (isset($prodValue->price) && !((float) $budget >= (float) $prodValue->price))
                    ) {
                        $category->products->splice($prodKey, 1);
                    }
                }
            }
        }

        return $category;
    }

    public function checkQueueDbConnection()
    {
        try {
            $result = DB::connection('queue_db')->select('SELECT 1 as test');

            $response = [
                'status' => 'success',
                'message' => 'Queue DB connection is working!',
                'result' => $result,
            ];
        } catch (\Exception $e) {
            $response = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }

        dd($response);
    }
}
