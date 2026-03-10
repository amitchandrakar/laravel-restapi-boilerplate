<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\Attribute\CategoryAttribute;
use App\Models\Traits\Scope\CustomScope;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Category Model
 *
 * Represents product categories with hierarchical structure:
 * - Parent/child category relationships
 * - State-based product loading
 * - Invitee visibility controls
 * - Store availability management
 * - URL routing and image associations
 * - Box lunch (bulk) category handling
 */
class Category extends BaseModel
{
    use CategoryAttribute, CustomScope, SoftDeletes;

    const ENTITY_TYPE = 'OjCategories';

    const BULK = 2;

    protected $table = 'oj_categories';

    /**
     * Get menu categories for display
     *
     * Retrieves active categories with images and URLs, optionally filtered
     * for group order invitees based on visibility settings.
     *
     * @param  bool  $requestFromInvitee  Whether request is from group order invitee
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getCategories($requestFromInvitee)
    {
        // Build query with necessary relationships and filters
        $category = Category::with(['uniqueurl', 'image'])
            ->availableInStore()
            ->parent()
            ->active()
            ->displayStatus()
            ->orderBy('display_order');

        // Filter for invitee visibility if needed
        if ($requestFromInvitee) {
            $category->where('visible_to_invitee', 1);
        }

        return $category->get();
    }

    /**
     * Get category with products and subcategories for state
     *
     * Loads complete category hierarchy with products, packages, pricing,
     * and related data for a specific state. Includes deep relationship loading
     * for product variants, dietary info, and images.
     *
     * @param  int  $id  Category ID
     * @param  int  $stateId  State ID for pricing
     * @param  bool  $requestFromInvitee  Whether request is from invitee
     * @return Category|null Category with loaded relationships
     */
    public static function getCategoryAndProductList($id, $stateId, $requestFromInvitee)
    {
        $category = Category::with([
            'subCategories' => function ($query) use ($stateId) {
                return $query
                    ->with([
                        'products' => function ($query) use ($stateId) {
                            return $query
                                ->with([
                                    'packages' => function ($query) use ($stateId) {
                                        return $query
                                            ->with([
                                                'statePrice' => function ($query) use ($stateId) {
                                                    return $query->where('state_id', $stateId);
                                                },
                                            ])
                                            ->active();
                                    },
                                    'dietary',
                                    'image',
                                    'uniqueurl',
                                ])
                                ->active()
                                ->orderBy('display_order');
                        },
                        'uniqueurl',
                    ])
                    ->active()
                    ->displayStatus()
                    ->orderBy('display_order');
            },
            'products' => function ($query) use ($stateId) {
                return $query
                    ->with([
                        'packages' => function ($query) use ($stateId) {
                            return $query
                                ->with([
                                    'statePrice' => function ($query) use ($stateId) {
                                        return $query->where('state_id', $stateId);
                                    },
                                ])
                                ->active();
                        },
                        'dietary',
                        'image',
                        'uniqueurl',
                    ])
                    ->active()
                    ->orderBy('display_order');
            },
            'uniqueurl',
        ])
            ->where('id', $id)
            ->availableInStore()
            ->active()
            ->displayStatus()
            ->orderBy('display_order')
            ->first();
        if ($category) {
            $category->state_id = $stateId;
            $category->touch();
        }

        return $category;
    }

    /**
     * Touch category and format related data
     *
     * Formats products and subcategories with state-specific data.
     * Called during category loading to prepare display data.
     *
     * @param  string|null  $attribute  Unused parameter
     * @return void
     */
    public function touch($attribute = null)
    {
        $this->menu_category_id = $this->id;

        // Format products with state-specific pricing
        if ($this->products->isNotEmpty()) {
            Product::formatProducts($this->products, $this->state_id);
        }

        // Recursively format subcategories
        if ($this->subCategories->isNotEmpty()) {
            $this->subCategories->map(function ($cat) {
                $this->formatCategory($cat);
                $cat->state_id = $this->state_id;
                $cat->touch();
            });
        }
    }

    public static function formatCategory($categories)
    {
        return $categories->identify = $categories->uniqueurl->url;
    }

    /**
     * Get category URL with parent hierarchy
     *
     * Generates URL for category navigation including parent category path.
     *
     * @param  int  $id  Category ID
     * @return string Category URL with hash fragment
     */
    public static function getCategoryUrl($id)
    {
        $category = Category::with(['parent', 'uniqueurl'])->find($id);
        $parentCategory = $category->getParentCategory();

        return $parentCategory->uniqueurl->url . '/#' . $category->uniqueurl->url;
    }

    /**
     * Get top-level parent category
     *
     * Recursively traverses category hierarchy to find root parent category.
     *
     * @return Category Root parent category
     */
    public function getParentCategory()
    {
        $category = $this;
        if ($category->parent_id > 0) {
            $category = $category->parent->getParentCategory();
        }
        if (!$category->parent_id) {
            return $category;
        }
    }

    public function subCategories()
    {
        return $this->hasMany('App\Models\Category', 'parent_id')->active()->availableInStore();
        // ->with(['subCategories', 'products']); // 👈 recursive magic here
    }

    public function parent()
    {
        return $this->hasOne('App\Models\Category', 'id', 'parent_id')->active()->availableInStore();
    }

    public function products()
    {
        return $this->hasMany('App\Models\Product', 'category_id')->active()->availableInStore();
        // ->with(['variant.option.selections']);
    }

    public function image()
    {
        return $this->hasOne('App\Models\Image', 'entity_id')->where(['entity_type' => self::ENTITY_TYPE]);
    }

    public function uniqueurl()
    {
        return $this->hasOne('App\Models\UniqueUrl', 'entity_id')->where(['entity_type' => self::ENTITY_TYPE]);
    }

    public function product()
    {
        return $this->hasMany(Product::class, 'product_id')->active()->availableInStore();
    }

    public function item()
    {
        return $this->hasMany(CartItem::class, 'category_id');
    }

    public function parentCategory()
    {
        return $this->hasOne(self::class, 'parent_id')->active()->availableInStore();
    }

    public function availableStore()
    {
        return $this->hasMany('App\Models\FoodAvailableStore', 'entity_id')->where([
            'entity_name' => self::ENTITY_TYPE,
        ]);
    }

    public function getCategoryById($id)
    {
        $id = is_array($id) ? $id : [$id];

        return Category::whereIn('id', $id)->get();
    }

    /**
     * Get categories available for invitee default meals
     *
     * Retrieves categories and products marked as available for group order
     * invitee default meal selection with complete pricing and option data.
     *
     * @param  bool  $requestFromInvitee  Whether request is from invitee
     * @param  int  $stateId  State ID for pricing
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getInviteeCategories($requestFromInvitee, $stateId)
    {
        $category = Category::with([
            'products' => function ($q) use ($stateId) {
                return $q->where(['invitee_default_meal' => 1])->with([
                    'packages' => function ($q) use ($stateId) {
                        return $q->where(['invitee_default_meal' => 1])->with([
                            'statePrice' => function ($query) use ($stateId) {
                                return $query->where('state_id', $stateId);
                            },
                            'options' => function ($query) use ($stateId) {
                                return $query
                                    ->where(['invitee_default_meal' => 1])
                                    ->with([
                                        'selections' => function ($query) use ($stateId) {
                                            return $query
                                                ->with([
                                                    'statePrice' => function ($query) use ($stateId) {
                                                        return $query->where('state_id', $stateId);
                                                    },
                                                    'image',
                                                    'dietary',
                                                ])
                                                ->orderBy('display_order');
                                        },
                                    ])
                                    ->availableInStore()
                                    ->active()
                                    ->orderBy('display_order');
                            },
                        ]);
                    },
                    'image',
                ]);
            },
        ])
            ->availableInStore()
            ->active()
            ->displayStatus()
            ->orderBy('display_order');

        if ($requestFromInvitee) {
            $category->where(['visible_to_invitee' => 1, 'invitee_default_meal' => 1]);
        }

        return $category->get();
    }

    /**
     * Get product IDs from categories based on conditions
     *
     * Parses category conditions string and extracts product IDs
     * from matching parent and child categories.
     *
     * @param  string  $splCond  Comma-separated category conditions
     * @return array Array of product IDs
     */
    public function getCategoryWithProducts($splCond = [])
    {
        // Parse category conditions (Example: 'Breakfast-Packages,Breakfast-Warm Selections,Beverages')
        $listCategories = explode(',', $splCond);
        $categoryVal = [];
        $i = 0;
        $productIds = [];
        // Parse parent-child category relationships
        foreach ($listCategories as $val) {
            $separate = explode('-', $val);
            if (count($separate) > 1) {
                $categoryVal[$i]['parent'] = $separate[0];
                $categoryVal[$i]['child'] = $separate[1];
            } else {
                $categoryVal[$i]['parent'] = $separate[0];
            }
            $i++;
        }
        // Process category conditions and collect product IDs
        if (!empty($categoryVal)) {
            foreach ($categoryVal as $cat) {
                // Handle parent-child category combinations
                if (isset($cat['parent']) && isset($cat['child'])) {
                    $parentVal = Category::where([
                        'name' => $cat['parent'],
                        'parent_id' => null,
                        'deleted_at' => null,
                    ])->first();
                    if (!empty($parentVal)) {
                        $childCategoryAndProducts = Category::where([
                            'parent_id' => $parentVal->id,
                            'name' => $cat['child'],
                            'deleted_at' => null,
                        ])
                            ->with(['products'])
                            ->first();
                        if (!empty($childCategoryAndProducts)) {
                            foreach ($childCategoryAndProducts->products as $prod) {
                                $productIds[] = $prod->id;
                            }
                        }
                    }
                    // Handle parent-only categories
                } elseif (isset($cat['parent'])) {
                    $categoryAndProducts = Category::where([
                        'parent_id' => null,
                        'OjCategories.name' => $cat['parent'],
                        'deleted_at' => null,
                    ])
                        ->with(['products'])
                        ->first();
                    if (!empty($categoryAndProducts)) {
                        foreach ($categoryAndProducts->products as $prod) {
                            $productIds[] = $prod->id;
                        }
                    }
                }
            }
        }

        return $productIds;
    }
}
