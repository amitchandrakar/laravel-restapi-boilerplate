<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Category;
use App\Models\Product;
use App\Models\Zipcode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Search API
 *
 * Keyword search across product names and category names.
 * Returns matching products and categories with pagination.
 */
class SearchController extends Controller
{
    /**
     * Search products and categories (no auth required).
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|min:1|max:255',
            'page' => 'sometimes|integer|min:1',
            'limit' => 'sometimes|integer|min:1|max:100',
        ], [
            'q.required' => 'The q field is required.',
        ]);

        $query = trim((string) $validated['q']);
        $page = (int) ($validated['page'] ?? 1);
        $limit = (int) ($validated['limit'] ?? 10);
        $stateId = $this->getDefaultStateId();

        $productsPaginator = $this->searchProducts($query, $page, $limit, $stateId);
        $categoriesPaginator = $this->searchCategories($query, $page, $limit);

        $data = [
            'query' => $query,
            'products' => $this->buildSearchPaginatedList($productsPaginator),
            'categories' => $this->buildSearchPaginatedList($categoriesPaginator),
        ];

        return $this->successResponse($data, 'Search results fetched successfully', 200);
    }

    private function getDefaultStateId(): ?int
    {
        $stateId = Zipcode::where('zipcode', '77074')->value('state_id');

        return $stateId ? (int) $stateId : null;
    }

    private function searchProducts(string $query, int $page, int $limit, ?int $stateId): LengthAwarePaginator
    {
        $q = Product::with(['uniqueurl', 'category.uniqueurl', 'packages' => function ($q) use ($stateId) {
            if ($stateId) {
                $q->with(['statePrice' => fn ($q) => $q->where('state_id', $stateId)]);
            }
        }])
            ->where('name', 'like', '%' . $query . '%')
            ->active()
            ->availableInStore()
            ->orderBy('display_order');

        return $q->paginate($limit, ['*'], 'page', $page);
    }

    private function searchCategories(string $query, int $page, int $limit): LengthAwarePaginator
    {
        return Category::with('uniqueurl')
            ->parent()
            ->where('name', 'like', '%' . $query . '%')
            ->active()
            ->displayStatus()
            ->orderBy('display_order')
            ->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * Build { items, pagination } from a LengthAwarePaginator, formatting products/categories for response.
     */
    private function buildSearchPaginatedList(LengthAwarePaginator $paginator): array
    {
        $resource = $paginator->getCollection();
        $isProduct = $resource->first() instanceof Product;

        $items = $resource->map(function ($item) use ($isProduct) {
            if ($isProduct) {
                return $this->formatProductForSearch($item);
            }

            return $this->formatCategoryForSearch($item);
        })->values()->all();

        return [
            'items' => $items,
            'pagination' => [
                'page' => $paginator->currentPage(),
                'limit' => $paginator->perPage(),
                'total' => $paginator->total(),
                'totalPages' => $paginator->lastPage(),
                'hasNext' => $paginator->hasMorePages(),
                'hasPrev' => $paginator->currentPage() > 1,
            ],
        ];
    }

    /**
     * @param  \App\Models\Product  $product
     * @return array<string, mixed>
     */
    private function formatProductForSearch($product): array
    {
        $slug = $product->uniqueurl?->url ?? '';
        $categorySlug = $product->category?->uniqueurl?->url ?? '';
        $firstVariant = $product->packages->first();
        $basePrice = 0;
        if ($firstVariant && $firstVariant->statePrice && isset($firstVariant->statePrice->price)) {
            $basePrice = (float) $firstVariant->statePrice->price;
        }
        $imageUrl = $product->image
            ? ($product->image->product_image_path['large'] ?? $product->image->image_path ?? null)
            : null;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $slug,
            'category' => $categorySlug,
            'basePrice' => $basePrice,
            'rating' => 4.7,
            'defaultImageUrl' => $imageUrl,
        ];
    }

    /**
     * @param  \App\Models\Category  $category
     * @return array<string, mixed>
     */
    private function formatCategoryForSearch($category): array
    {
        $slug = $category->uniqueurl?->url ?? '';

        return [
            'title' => $category->name,
            'slug' => $slug,
            'productCount' => $category->getProductCountIncludingDescendants(),
        ];
    }
}
