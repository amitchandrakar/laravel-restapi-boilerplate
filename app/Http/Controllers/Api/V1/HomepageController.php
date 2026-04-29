<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Category;
use App\Models\Product;
use App\Models\Zipcode;
use Illuminate\Http\JsonResponse;

/**
 * Homepage API
 *
 * Returns all data needed to render the homepage: hero, group order section,
 * category list, featured products, and testimonials.
 */
class HomepageController extends Controller
{
    /**
     * Get homepage data (no auth required).
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $requestFromInvitee = (bool) config('app.request-from-invitee', false);
        $categories = Category::getCategories($requestFromInvitee);

        $heroSection = array_merge(config('alonti.hero', []), [
            'headline' => config('alonti.hero.headline', 'Professional Catering. No Middlemen.'),
            'subhead' => config('alonti.hero.subhead', 'Scratch-made meals, in-house drivers...'),
        ]);

        $groupOrderSection = config('alonti.group_order_section', []);

        $categorySection = [
            'title' => 'Menus That Solve How You Work',
            'description' => 'From all-day training to boardroom classics...',
            'categories' => $this->formatCategoriesForHomepage($categories),
        ];

        $limit = (int) config('alonti.featured_products_limit', 5);
        $featuredProductsSection = [
            'title' => 'Featured Products',
            'description' => 'Our most popular catering packages...',
            'products' => $this->getFeaturedProducts($limit),
        ];

        $testimonialsLimit = (int) config('alonti.testimonials_limit', 6);
        $testimonials = config('alonti.testimonials', []);
        $testimonialSection = [
            'title' => 'Why Corporate Teams Choose Alonti',
            'description' => 'Fifty years of professional catering...',
            'testimonials' => [
                'items' => array_slice($testimonials, 0, $testimonialsLimit),
                'pagination' => [
                    'page' => 1,
                    'limit' => $testimonialsLimit,
                    'total' => count($testimonials),
                    'totalPages' => (int) max(1, ceil(count($testimonials) / $testimonialsLimit)),
                    'hasNext' => false,
                    'hasPrev' => false,
                ],
            ],
        ];

        $data = [
            'heroSection' => $heroSection,
            'groupOrderSection' => $groupOrderSection,
            'categorySection' => $categorySection,
            'featuredProductsSection' => $featuredProductsSection,
            'testimonialSection' => $testimonialSection,
        ];

        return $this->successResponse($data, 'Homepage data fetched successfully', 200);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Category>  $categories
     * @return array<int, array<string, mixed>>
     */
    private function formatCategoriesForHomepage($categories): array
    {
        $list = [];
        foreach ($categories as $index => $cat) {
            $slug = $cat->uniqueurl?->url ?? '';
            $list[] = [
                'title' => $cat->name ?? '',
                'slug' => $slug,
                'description' => $cat->description ?? null,
                'imageUrl' => $cat->image?->image_path ?? null,
                'popular' => $index === 0,
                'productCount' => $cat->getProductCountIncludingDescendants(),
                'ribbon' =>
                    $index === 0
                        ? ['type' => 'corner-diagonal', 'label' => 'Most Popular', 'variant' => 'primary']
                        : ($index === 2
                            ? ['type' => 'corner-diagonal', 'label' => 'New', 'variant' => 'secondary']
                            : null),
            ];
        }

        return $list;
    }

    /**
     * Featured products with pagination shape.
     *
     * @return array{items: array<int, array<string, mixed>>, pagination: array<string, int|bool>}
     */
    private function getDefaultStateId(): ?int
    {
        $stateId = Zipcode::where('zipcode', '77074')->value('state_id');

        return $stateId ? (int) $stateId : null;
    }

    private function getFeaturedProducts(int $limit): array
    {
        $stateId = $this->getDefaultStateId();
        $products = Product::with([
            'uniqueurl',
            'category.uniqueurl',
            'image',
            'packages' => fn ($q) => $q->with(['statePrice' => fn ($q) => $q->where('state_id', $stateId)]),
        ])
            ->where('is_featured', 1)
            ->active()
            ->availableInStore()
            ->orderBy('display_order')
            ->limit($limit)
            ->get();

        $items = [];
        foreach ($products as $p) {
            $firstPackage = $p->packages->first();
            $price = $firstPackage && $firstPackage->statePrice
                ? (float) $firstPackage->statePrice->price
                : 0;
            $categorySlug = $p->category?->uniqueurl?->url ?? '';
            $subcategorySlug = '';
            $items[] = [
                'id' => $p->id,
                'name' => $p->name ?? '',
                'description' => $p->description ?? '',
                'price' => $price,
                'originalPrice' => $price,
                'discountPercent' => null,
                'rating' => 4.7,
                'imageKey' => 'product_' . $p->id,
                'imageUrl' => $p->image?->image_path ?? null,
                'category' => $categorySlug,
                'subcategory' => $subcategorySlug,
                'dietary' => [],
                'servings' => null,
            ];
        }

        $total = $products->count();

        return [
            'items' => $items,
            'pagination' => [
                'page' => 1,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => (int) max(1, ceil($total / $limit)),
                'hasNext' => false,
                'hasPrev' => false,
            ],
        ];
    }
}
