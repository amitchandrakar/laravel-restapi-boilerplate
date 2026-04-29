<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Zipcode;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Global Context API
 *
 * Returns site-wide configuration used across all pages: operational zip codes,
 * category list, social media, logged-in user details, cart/wishlist counts, help.
 */
class GlobalContextController extends Controller
{
    /**
     * Get global context (no auth required).
     *
     * @return JsonResponse
     */
    public function globalContext(): JsonResponse
    {
        $requestFromInvitee = (bool) config('app.request-from-invitee', false);
        $categories = Category::getCategories($requestFromInvitee);

        $operationalZipCodes = Zipcode::where('status', 1)
            ->orderBy('zipcode')
            ->pluck('zipcode')
            ->map(fn ($zip) => (string) $zip)
            ->values()
            ->all();

        $deliveryZipCode = null;
        $isUserLoggedIn = false;
        $loggedInUserDetails = null;
        $cartItemCount = 0;
        $wishlistItemCount = 0;
        $notificationsCount = 0;

        if (Auth::guard('sanctum')->check() || Auth::check()) {
            $user = Auth::guard('sanctum')->user() ?? Auth::user();
            $isUserLoggedIn = true;
            $deliveryZipCode = $user->zip ? (string) $user->zip : ($operationalZipCodes[0] ?? null);
            $loggedInUserDetails = [
                'id' => $user->id,
                'name' => trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')) ?: $user->company_name ?? $user->email,
                'email' => $user->email,
                'fname' => $user->fname ?? '',
                'lname' => $user->lname ?? '',
            ];

            $cart = Cart::individual()->where('user_id', $user->id)->pending()->first();
            if ($cart) {
                $cartItemCount = $cart->getCartCount();
            }
            // Wishlist and notifications not implemented yet; use 0
        } else {
            $deliveryZipCode = $operationalZipCodes[0] ?? null;
        }

        $globalContext = [
            'operationalDeliveryZipCodes' => $operationalZipCodes,
            'deliveryZipCode' => $deliveryZipCode,
            'isUserLoggedIn' => $isUserLoggedIn,
            'isGroupOrder' => false,
            'isInviteeOrder' => $requestFromInvitee,
            'cartItemCount' => $cartItemCount,
            'wishlistItemCount' => $wishlistItemCount,
            'notificationsCount' => $notificationsCount,
            'loggedInUserDetails' => $loggedInUserDetails,
            'categories' => $this->formatCategoriesForGlobal($categories),
            'socialMediaDetails' => config('alonti.social_media', []),
            'help' => config('alonti.help', ['cafe' => [], 'contacts' => []]),
        ];

        $metaExtra = ['endpoint' => 'GET /api/v1/global-context'];

        return $this->successResponse(
            ['globalContext' => $globalContext],
            'Global context fetched successfully',
            200
        );
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Category>  $categories
     * @return array<int, array{id: int, name: string, slug: string}>
     */
    private function formatCategoriesForGlobal($categories): array
    {
        $list = [];
        foreach ($categories as $cat) {
            $slug = $cat->uniqueurl?->url ?? '';
            $list[] = [
                'id' => $cat->id,
                'name' => $cat->name ?? '',
                'slug' => $slug,
            ];
        }

        return $list;
    }
}
