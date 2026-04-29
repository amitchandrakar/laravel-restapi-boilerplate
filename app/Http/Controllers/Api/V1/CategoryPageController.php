<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Category;
use App\Models\Dietary;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\UniqueUrl;
use App\Models\Zipcode;
use Illuminate\Http\JsonResponse;

/**
 * Category Page API
 *
 * Returns all categories (GET /categories) or full details for a single category
 * by slug (GET /categories/{slug}): metadata, ribbon, sub-categories with product
 * listings, variants, options, add-ons, and reviews.
 */
class CategoryPageController extends Controller
{
    /**
     * Get all categories with full hierarchy (no auth required).
     *
     * Each category has the same structure as GET /categories/{slug}: subCategories
     * with products and variants (options/selections, addons, nutritionalFacts, reviews).
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $requestFromInvitee = (bool) config('app.request-from-invitee', false);
        $categories = Category::getCategories($requestFromInvitee);
        $stateId = $this->getDefaultStateId();
        $list = [];

        foreach ($categories as $cat) {
            $slug = $cat->uniqueurl?->url ?? '';
            $fullCategory = Category::getCategoryAndProductList($cat->id, $stateId, $requestFromInvitee);
            if (!$fullCategory) {
                continue;
            }
            $this->loadVariantOptionsAndAddons($fullCategory);
            $list[] = $this->formatCategoryForApi($fullCategory, $slug);
        }

        $dietaries = $this->getAllDietariesForFilters();

        return $this->successResponse(
            [
                'categories' => $list,
                'dietaries' => $dietaries,
            ],
            'Categories fetched successfully',
            200
        );
    }

    /**
     * Get category page by slug (no auth required).
     *
     * @return JsonResponse
     */
    public function show(string $slug): JsonResponse
    {
        $url = UniqueUrl::where('url', $slug)->where('entity_type', Category::ENTITY_TYPE)->first();

        if (!$url || !$url->isCategory()) {
            return $this->notFoundResponse('Resource not found.');
        }

        $stateId = $this->getDefaultStateId();
        $requestFromInvitee = (bool) config('app.request-from-invitee', false);
        $category = Category::getCategoryAndProductList($url->entity_id, $stateId, $requestFromInvitee);

        if (!$category) {
            return $this->notFoundResponse('Resource not found.');
        }

        $this->loadVariantOptionsAndAddons($category);

        $categoryPayload = $this->formatCategoryForApi($category, $slug);
        $dietaries = $this->getAllDietariesForFilters();

        return $this->successResponse(
            [
                'category' => $categoryPayload,
                'dietaries' => $dietaries,
            ],
            'Category data fetched successfully',
            200
        );
    }

    /**
     * Get product details by unique URL (no auth required).
     *
     * @return JsonResponse
     */
    public function showProduct(string $uniqueUrl): JsonResponse
    {
        $url = UniqueUrl::where('url', $uniqueUrl)->where('entity_type', Product::ENTITY_TYPE)->first();

        if (!$url || !$url->isProduct()) {
            return $this->notFoundResponse('Product not found.');
        }

        $stateId = $this->getDefaultStateId();
        $product = Product::with([
            'packages' => function ($q) use ($stateId) {
                $q->with([
                    'statePrice' => fn($q) => $q->where('state_id', $stateId),
                    'image',
                ])->active();
            },
            'dietary',
            'image',
            'uniqueurl',
            'category' => fn($q) => $q->with(['uniqueurl', 'parent.uniqueurl']),
        ])
            ->active()
            ->find($url->entity_id);

        if (!$product) {
            return $this->notFoundResponse('Product not found.');
        }

        $this->loadVariantOptionsAndAddonsForProduct($product, $stateId);

        $categorySlug = '';
        $subcategorySlug = '';
        if ($product->relationLoaded('category') && $product->category) {
            $cat = $product->category;
            if ($cat->parent_id && $cat->relationLoaded('parent') && $cat->parent) {
                $categorySlug = $cat->parent->uniqueurl?->url ?? '';
                $subcategorySlug = $cat->uniqueurl?->url ?? '';
            } else {
                $categorySlug = $cat->uniqueurl?->url ?? '';
                $subcategorySlug = '';
            }
        }

        $formatted = $this->formatProductForCategoryApi($product, $categorySlug, $subcategorySlug);
        $formatted['slug'] = $product->uniqueurl?->url ?? '';

        return $this->successResponse(['product' => $formatted], 'Product details fetched successfully', 200);
    }

    /**
     * Eager load options (and selections) and addons on a single product's packages.
     *
     * @param  \App\Models\Product  $product
     * @param  int|null  $stateId
     */
    private function loadVariantOptionsAndAddonsForProduct(Product $product, ?int $stateId): void
    {
        if ($stateId === null) {
            $stateId = $this->getDefaultStateId();
        }
        $withVariant = [
            'options' => function ($q) use ($stateId) {
                $q->with([
                    'selections' => function ($q) use ($stateId) {
                        $q->with(['statePrice' => fn($q) => $q->where('state_id', $stateId)], 'image', 'dietary');
                    },
                ]);
            },
            'addons' => function ($q) use ($stateId) {
                $q->with([
                    'packages' => function ($q) use ($stateId) {
                        $q->with(['statePrice' => fn($q) => $q->where('state_id', $stateId)]);
                    },
                ]);
            },
        ];
        $product->packages->load($withVariant);

        // #region agent log
        $logPath = base_path('.cursor/debug-69fd53.log');
        $pkg = $product->packages->first();
        $opt = $pkg ? $pkg->options->first() : null;
        $selectionsLoaded = $opt ? $opt->relationLoaded('selections') : false;
        $selectionsCount = $opt && $selectionsLoaded ? ($opt->selections ? $opt->selections->count() : 0) : -1;
        $pivotCount = $opt
            ? \Illuminate\Support\Facades\DB::table('oj_product_option_selections')
                ->where('product_option_id', $opt->id)
                ->whereNull('deleted_at')
                ->count()
            : -1;
        file_put_contents(
            $logPath,
            json_encode([
                'sessionId' => '69fd53',
                'hypothesisId' => 'H1',
                'location' => 'CategoryPageController::loadVariantOptionsAndAddonsForProduct',
                'message' => 'after load',
                'data' => [
                    'stateId' => $stateId,
                    'packageId' => $pkg?->id,
                    'optionsCount' => $pkg ? $pkg->options->count() : 0,
                    'firstOptionId' => $opt?->id,
                    'selectionsRelationLoaded' => $selectionsLoaded,
                    'selectionsCount' => $selectionsCount,
                    'pivotRowCountNonDeleted' => $pivotCount,
                ],
                'timestamp' => time() * 1000,
            ]) . "\n",
            FILE_APPEND
        );
        // #endregion
    }

    private function getDefaultStateId(): ?int
    {
        $stateId = Zipcode::where('zipcode', '77074')->value('state_id');

        return $stateId ? (int) $stateId : null;
    }

    /**
     * All dietaries from oj_dietaries for filter UI (id + name).
     *
     * @return array<int, array{id: int, name: string}>
     */
    private function getAllDietariesForFilters(): array
    {
        return Dietary::active()
            ->orderBy('id')
            ->get()
            ->map(fn($d) => ['id' => (int) $d->id, 'name' => trim((string) ($d->name ?? ''))])
            ->values()
            ->all();
    }

    /**
     * Product dietaries for API.
     *
     * Primary source: direct product–dietary link via oj_product_dietaries
     * (oj_product_dietaries.product_id = oj_products.id AND oj_product_dietaries.dietary_id = oj_dietaries.id).
     * One product may have multiple dietaries. If that relation is empty, dietaries are derived from
     * option selections across the product's variants (packages).
     *
     * @return array<int, array{id: int, name: string}>
     */
    private function getProductDietariesForApi(Product $product): array
    {
        // Simple case: product dietaries from oj_product_dietaries pivot
        if ($product->relationLoaded('dietary') && $product->dietary && $product->dietary->isNotEmpty()) {
            return $product->dietary
                ->map(fn($d) => ['id' => (int) $d->id, 'name' => trim((string) ($d->name ?? ''))])
                ->values()
                ->all();
        }

        $packages = $product->packages ?? collect();
        $dietaries = $packages
            ->flatMap(fn($pkg) => $pkg->relationLoaded('options') ? $pkg->options ?? collect() : collect())
            ->flatMap(fn($opt) => $opt->relationLoaded('selections') ? $opt->selections ?? collect() : collect())
            ->flatMap(function ($sel) {
                if (!$sel->relationLoaded('dietary') || !$sel->dietary) {
                    return collect();
                }
                return $sel->dietary;
            })
            ->unique('id')
            ->values()
            ->map(fn($d) => ['id' => (int) $d->id, 'name' => trim((string) ($d->name ?? ''))])
            ->values()
            ->all();

        return $dietaries;
    }

    /**
     * Eager load options (and selections) and addons on variant packages for API output.
     *
     * @param  \App\Models\Category  $category
     */
    private function loadVariantOptionsAndAddons($category): void
    {
        $stateId = $this->getDefaultStateId();
        $withVariant = [
            'options' => function ($q) use ($stateId) {
                $q->with([
                    'selections' => function ($q) use ($stateId) {
                        $q->with(['statePrice' => fn($q) => $q->where('state_id', $stateId)], 'image', 'dietary');
                    },
                ]);
            },
            'addons' => function ($q) use ($stateId) {
                $q->with([
                    'packages' => function ($q) use ($stateId) {
                        $q->with(['statePrice' => fn($q) => $q->where('state_id', $stateId)]);
                    },
                ]);
            },
        ];

        $category->subCategories->each(function ($sub) use ($withVariant) {
            $sub->products->each(fn($p) => $p->packages->load($withVariant));
        });
        $category->products->each(fn($p) => $p->packages->load($withVariant));
    }

    /**
     * @param  \App\Models\Category  $category
     * @return array<string, mixed>
     */
    private function formatCategoryForApi($category, string $slug): array
    {
        $parentSlug = $category->uniqueurl?->url ?? $slug;
        $description =
            $category->description !== null && trim((string) $category->description) !== ''
                ? trim((string) $category->description)
                : null;

        return [
            'id' => $category->id,
            'name' => trim((string) ($category->name ?? '')),
            'slug' => $parentSlug,
            'description' => $description,
            'imageUrl' => $category->image?->image_path ?? null,
            'productCount' =>
                $category->products->count() + $category->subCategories->sum(fn($s) => $s->products->count()),
            'sequence' => (int) ($category->display_order ?? 0),
            'popular' => (bool) ($category->display_order === 1),
            'ribbon' =>
                $category->display_order === 1
                    ? ['type' => 'corner-diagonal', 'label' => 'Most Popular', 'variant' => 'primary']
                    : ($category->display_order === 2
                        ? ['type' => 'corner-diagonal', 'label' => 'New', 'variant' => 'secondary']
                        : null),
            'subCategories' => $category->subCategories
                ->map(fn($sub) => $this->formatSubCategoryForApi($sub, $parentSlug))
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  \App\Models\Category  $sub
     * @return array<string, mixed>
     */
    private function formatSubCategoryForApi($sub, string $parentSlug): array
    {
        $subSlug = $sub->uniqueurl?->url ?? '';

        return [
            'id' => $sub->id,
            'name' => trim((string) ($sub->name ?? '')),
            'slug' => $subSlug,
            'productCount' => $sub->products->count(),
            'sequence' => (int) ($sub->display_order ?? 0),
            'products' => $sub->products
                ->map(fn($product) => $this->formatProductForCategoryApi($product, $parentSlug, $subSlug))
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  \App\Models\Product  $product
     * @return array<string, mixed>
     */
    private function formatProductForCategoryApi(
        $product,
        string $categorySlug = '',
        string $subcategorySlug = ''
    ): array {
        $productSlug = $product->uniqueurl?->url ?? '';
        $variants = $product->packages ?? collect();

        $dietaries = $this->getProductDietariesForApi($product);

        return [
            'id' => $product->id,
            'name' => trim((string) ($product->name ?? '')),
            'dietary' => $dietaries,
            'variants' => $variants
                ->map(fn($v) => $this->formatVariantForApi($v, $product, $productSlug, $categorySlug, $subcategorySlug))
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  \App\Models\ProductVariant  $variant
     * @param  \App\Models\Product|null  $product
     * @return array<string, mixed>
     */
    private function formatVariantForApi(
        $variant,
        $product = null,
        string $productSlug = '',
        string $categorySlug = '',
        string $subcategorySlug = ''
    ): array {
        $basePrice = isset($variant->statePrice->price) ? (float) $variant->statePrice->price : 0;
        $originalPrice = $basePrice;
        $discountPercent = 0;

        $options = $variant->relationLoaded('options') ? $variant->options ?? collect() : collect();
        // #region agent log
        $firstOpt = $options->first();
        if ($firstOpt) {
            $logPath = base_path('.cursor/debug-69fd53.log');
            file_put_contents(
                $logPath,
                json_encode([
                    'sessionId' => '69fd53',
                    'hypothesisId' => 'H4',
                    'location' => 'CategoryPageController::formatVariantForApi',
                    'message' => 'first option selections',
                    'data' => [
                        'variantId' => $variant->id,
                        'firstOptionId' => $firstOpt->id,
                        'selectionsLoaded' => $firstOpt->relationLoaded('selections'),
                        'selectionsCount' =>
                            $firstOpt->relationLoaded('selections') && $firstOpt->selections
                                ? $firstOpt->selections->count()
                                : 0,
                    ],
                    'timestamp' => time() * 1000,
                ]) . "\n",
                FILE_APPEND
            );
        }
        // #endregion
        $optionList = $options
            ->map(function ($opt) use ($variant) {
                $selections = $opt->relationLoaded('selections') ? $opt->selections ?? collect() : collect();
                return [
                    'id' => $opt->id,
                    'productVariantId' => $opt->product_variant_id ?? $variant->id,
                    'name' => $opt->name ?? ($opt->option_name ?? ''),
                    'type' => $opt->option_type ?? 'radio',
                    'required' => (bool) ($opt->required ?? true),
                    'maxSelections' => (int) ($opt->max_selections ?? 1),
                    'selections' => $selections
                        ->map(
                            fn($s) => [
                                'id' => $s->id,
                                'name' => $s->name ?? ($s->selection_name ?? ''),
                                'imageUrl' =>
                                    $s->relationLoaded('image') && $s->image ? $s->image->small_image_path : null,
                                'mediumImageUrl' =>
                                    $s->relationLoaded('image') && $s->image ? ($s->image->medium_image_path ?? null) : null,
                                'imagePath' =>
                                    $s->relationLoaded('image') && $s->image ? ($s->image->image_path ?? null) : null,
                                'shortDescription' => $s->short_description ?? null,
                                'isFree' =>
                                    (bool) ((isset($s->statePrice->price) ? (float) $s->statePrice->price : 0) == 0),
                                'price' => (float) (isset($s->statePrice->price) ? $s->statePrice->price : 0),
                                'dietaries' =>
                                    $s->relationLoaded('dietary') && $s->dietary
                                        ? $s->dietary->pluck('name')->all()
                                        : [],
                                'sequence' => (int) ($s->display_order ?? 0),
                            ]
                        )
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();

        $addons = $variant->relationLoaded('addons') ? $variant->addons ?? collect() : collect();
        $addonList = $addons
            ->map(function ($addon) {
                $addonPackages = $addon->relationLoaded('packages') ? $addon->packages ?? collect() : collect();
                $firstPrice =
                    $addonPackages->first() && isset($addonPackages->first()->statePrice->price)
                        ? (float) $addonPackages->first()->statePrice->price
                        : 0;
                $selections = $addonPackages->isNotEmpty()
                    ? $addonPackages->map(fn($pkg) => ['id' => $pkg->id, 'name' => $pkg->name ?? ''])->values()->all()
                    : [['id' => $addon->id, 'name' => $addon->name ?? '']];
                return [
                    'id' => $addon->id,
                    'name' => $addon->name ?? '',
                    'price' => $firstPrice,
                    'selections' => $selections,
                ];
            })
            ->values()
            ->all();

        // Category variants (oj_product_variants) don't reliably have their own image row,
        // but products do (same as Homepage featured products). Use the product image here.
        $defaultImageUrl = $product?->image?->image_path;
        $dummyImageUrls = [
            'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=800',
            'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=800',
            'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=800',
        ];
        $imageUrls = $defaultImageUrl
            ? array_slice(array_merge([$defaultImageUrl], $dummyImageUrls), 0, 3)
            : $dummyImageUrls;

        $productDietaries = $product ? $this->getProductDietariesForApi($product) : [];

        $shortDesc = $variant->short_description ?? ($variant->description ?? ($product->description ?? null));
        $shortDescription = $shortDesc !== null && trim((string) $shortDesc) !== '' ? trim((string) $shortDesc) : null;

        return [
            'id' => $variant->id,
            'name' => trim((string) ($variant->name ?? ($variant->package_name ?? ''))),
            'slug' => $productSlug,
            'category' => $categorySlug,
            'subcategory' => $subcategorySlug,
            'defaultImageUrl' => $defaultImageUrl,
            'imageUrls' => $imageUrls,
            'shortDescription' => $shortDescription,
            'sequence' => (int) ($variant->display_order ?? 0),
            'basePrice' => $basePrice,
            'originalPrice' => $originalPrice,
            'discountPercent' => $discountPercent,
            'rating' => 4.7,
            'dietaries' => $productDietaries,
            'servings' => $product ? (trim((string) ($product->serve ?? '')) ?: null) : $variant->serve ?? null,
            'quantityInterval' =>
                (int) ($variant->quantity_interval ?? (($product ? $product->quantity_interval : null) ?? 1)),
            'options' => $optionList,
            'nutritionalFacts' => [
                'calories' => 0,
                'protein' => '0g',
                'carbs' => '0g',
                'fat' => '0g',
                'fiber' => '0g',
                'sodium' => '0mg',
            ],
            'addons' => $addonList,
            'reviews' => [
                'items' => [],
                'pagination' => [
                    'page' => 1,
                    'limit' => 10,
                    'total' => 0,
                    'totalPages' => 0,
                    'hasNext' => false,
                    'hasPrev' => false,
                ],
            ],
        ];
    }
}
