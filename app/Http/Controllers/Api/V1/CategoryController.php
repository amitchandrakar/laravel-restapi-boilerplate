<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Alonti\Cart\CartManager;
use App\Http\Resources\Api\V1\CategoryIndexResource;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Dietary;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Category Controller
 *
 * Handles category display and homepage rendering with:
 * - Category listings for menu navigation
 * - Individual cart status checking
 * - Dietary preference filtering
 * - Banner and help content management
 */
class CategoryController extends Controller
{
    /**
     * Display homepage with category navigation
     *
     * Shows main menu categories, checks for individual cart status,
     * handles dietary preferences, and displays banner content
     *
     * @param  string|null  $invitee  Optional invitee context parameter
     * @return \Illuminate\Http\JsonResponse
     */
    public function index($invitee = null)
    {
        // Initialize homepage variables
        $lunchCategory = 'tinga-chicken-pita-box-lunch'; // Featured box lunch category
        $individualCart = false;

        // Check for existing individual cart
        if (Auth::user()) {
            $individualCart = Cart::individual()->mine()->pending()->first();
        }

        // Get active cart and dietary information
        $cartInfo = app(CartManager::class)->getActiveCart();
        $individualDietary = Dietary::where(['name' => 'Individually Packaged'])
            ->select('id')
            ->first();

        $individualDietaryId = $individualDietary ? $individualDietary->id : 0;
        $condition = ['parent_id' => null, 'display_status' => 1, 'status' => 1];

        // Get categories based on user context (regular vs invitee)
        $requestFromInvitee = config()->get('app.request-from-invitee');
        $categories = Category::getCategories($requestFromInvitee);
        // Prepare date and banner information
        $fromDate = Carbon::createFromFormat('Y-m-d', '2020-12-31');
        $currentDate = Carbon::now();
        $bannerSetting = DB::table('website_banner_settings')->first();

        // Check if help popup should be shown
        $help = request()->help ? true : false;

        return $this->successResponse(
            CategoryIndexResource::make([
                'categories' => $categories,
                'lunch_category' => $lunchCategory,
                'individual_cart' => $individualCart,
                'cart_info' => $cartInfo,
                'individual_dietary_id' => $individualDietaryId,
                'from_date' => $fromDate?->format('Y-m-d'),
                'current_date' => $currentDate->format('Y-m-d'),
                'help' => $help,
                'banner_setting' => $bannerSetting,
            ]),
            'Success'
        );
    }
}
