<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\Scope\CustomScope;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends BaseModel
{
    use CustomScope, SoftDeletes;

    const ENTITY_TYPE = 'OjProducts';

    protected $table = 'oj_products';

    public static function getProductsByCategororyId($categoryId, $stateId)
    {
        $products = Product::with([
            'packages' => function ($query) use ($stateId) {
                return $query
                    ->with([
                        'statePrice' => function ($query) use ($stateId) {
                            return $query->where('state_id', $stateId);
                        },
                        'packageSizes' => function ($query) use ($stateId) {
                            return $query->with([
                                'statePrice' => function ($query) use ($stateId) {
                                    return $query->where('state_id', $stateId);
                                },
                            ]);
                        },
                        'options' => function ($query) use ($stateId) {
                            return $query
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
                                ->active()
                                ->orderBy('display_order');
                        },
                        'addons' => function ($query) use ($stateId) {
                            return $query->with([
                                'packages' => function ($query) use ($stateId) {
                                    return $query->with([
                                        'statePrice' => function ($query) use ($stateId) {
                                            return $query->where('state_id', $stateId);
                                        },
                                        'options' => function ($query) use ($stateId) {
                                            return $query
                                                ->with([
                                                    'selections' => function ($query) use ($stateId) {
                                                        return $query
                                                            ->with([
                                                                'statePrice' => function ($query) use ($stateId) {
                                                                    return $query->where('state_id', $stateId);
                                                                },
                                                                'image',
                                                            ])
                                                            ->orderBy('display_order');
                                                    },
                                                ])
                                                ->active()
                                                ->orderBy('display_order');
                                        },
                                    ]);
                                },
                            ]);
                        },
                    ])
                    ->active();
            },
            'dietary',
            'image',
            'uniqueurl',
        ])
            ->where('category_id', $categoryId)
            ->active()
            ->orderBy('display_order')
            ->get();

        return Product::formatProducts($products, $stateId);
    }

    public function getWeightTypeAttribute()
    {
        if ($this->unit_type == 1 && $this->minimum_serve == 10) {
            return 'Number of servings';
        } elseif ($this->unit_type == 2) {
            return 'Dozen';
        } else {
            return 'Quantity';
        }
    }

    public static function getProductById($id, $stateId)
    {
        $product = Product::with([
            'packages' => function ($query) use ($stateId) {
                return $query
                    ->with([
                        'statePrice' => function ($query) use ($stateId) {
                            return $query->where('state_id', $stateId);
                        },
                        'packageSizes' => function ($query) use ($stateId) {
                            return $query->with([
                                'statePrice' => function ($query) use ($stateId) {
                                    return $query->where('state_id', $stateId);
                                },
                            ]);
                        },
                        'options' => function ($query) use ($stateId) {
                            return $query
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
                                ->active()
                                ->orderBy('display_order');
                        },
                        'addons' => function ($query) use ($stateId) {
                            return $query->with([
                                'packages' => function ($query) use ($stateId) {
                                    return $query->with([
                                        'statePrice' => function ($query) use ($stateId) {
                                            return $query->where('state_id', $stateId);
                                        },
                                        'options' => function ($query) use ($stateId) {
                                            return $query
                                                ->with([
                                                    'selections' => function ($query) use ($stateId) {
                                                        return $query
                                                            ->with([
                                                                'statePrice' => function ($query) use ($stateId) {
                                                                    return $query->where('state_id', $stateId);
                                                                },
                                                                'image',
                                                            ])
                                                            ->orderBy('display_order');
                                                    },
                                                ])
                                                ->active()
                                                ->orderBy('display_order');
                                        },
                                    ]);
                                },
                            ]);
                        },
                    ])
                    ->active();
            },
            'category' => function ($query) {
                return $query->with(['parent']);
            },
            'dietary',
            'image',
        ])->find($id);
        $product->touch();

        return $product;
    }

    public function touch($attribute = null)
    {
        $this->formatProductImage($this->image);
        $this->packages->map(function ($package) {
            if ($package->packageSizes->isNotEmpty()) {
                $packageSizeNames = config('custom.packageSizeNames');
                $package_name = 'sandwiches';
                foreach ($packageSizeNames as $key => $val) {
                    if (strripos($this->name, $key)) {
                        $package_name = $val;
                    }
                }
                $package->package_size_name = $package_name;
            }
            $this->formatProductPackages($package);

            if ($package->addons->isNotEmpty()) {
                $package->addons->map(function ($addon) {
                    $addon->package = $addon->packages[0];
                    $this->formatProductPackages($addon->package);
                });
            }
        });

        $this->package = $this->packages[0];
        $this->price = $this->package->statePrice->price;
        $this->getParentCategoryId($this->category);
    }

    public function getParentCategoryId($category)
    {
        if ($category) {
            if ($category->parent) {
                $this->getParentCategoryId($category->parent);
            } else {
                $this->menu_category_id = $category->id;
            }
        }
    }

    public function formatProductPackages($package)
    {
        if ($package->options->isNotEmpty()) {
            $package->options->map(function ($option) {
                $option->selections->map(function ($sel) {
                    $sel->image_path = config('custom.image.selection');
                    if ($sel->image) {
                        $sel->image_path = $sel->image->small_image_path;
                    }
                    $sel->statePrice = $sel->statePrice;
                    $sel->dietary = $sel->dietary;
                    $sel->selection_name = $sel->selection_name;
                    $sel->price = $sel->statePrice->price;
                });
                $option->error_message = $option->error_message;
            });
        }
    }

    public function formatProductImage()
    {
        $this->image_path = config('custom.image.product');
        if ($this->image) {
            $this->image_path = $this->image->large_image_path;
        }
    }

    public function formatProductDietary()
    {
        $dietary_names = [];
        if ($this->dietary->isNotEmpty()) {
            $this->dietary->map(function ($dietary) {
                $dietary->dietary_name = $dietary->dietary_name;

                return $dietary;
            });

            foreach ($this->dietary as $dietary) {
                $dietary_names[] = $dietary->name;
            }
        }
        $this->dietary_names = implode(',', $dietary_names);
    }

    public function formatProductVariant($stateId)
    {
        if ($this->packages->isNotEmpty()) {
            $this->packages->map(function ($package) use ($stateId) {
                $package->url = urlencode($package->name);
                $package->package_name = $package->package_name;
                $package->package_option = $package->package_option;
                $package->tooltip = $package->tooltip;
                $package->statePrice = $package->statePrice->getPrice(
                    $package->id,
                    ProductVariant::ENTITY_TYPE,
                    $stateId
                );

                if ($package->options->isNotEmpty()) {
                    $package->options->map(function ($option) {
                        $option->selections->map(function ($sel) {
                            $sel->image_path = config('custom.image.selection');
                            if ($sel->image) {
                                $sel->image_path = $sel->image->small_image_path;
                            }
                            $sel->statePrice = $sel->statePrice;
                            $sel->dietary = $sel->dietary;
                            $sel->selection_name = $sel->selection_name;
                            $sel->price = $sel->statePrice->price;
                        });
                        $option->error_message = $option->error_message;
                    });
                }

                return $package;
            });
        }
    }

    public static function formatProducts($products, $stateId)
    {
        return $products->map(function (Product $product) use ($stateId) {
            if (stripos($product->serve, 'serve') === false) {
                if ($product->unit_type == 2) {
                    if (stripos($product->serve, 'dozen') == false) {
                        $product->serve = $product->serve . ' dozen';
                    }
                } else {
                    $product->serve = 'Serves ' . $product->serve;
                }
            }
            $product->formatProductImage();
            $product->formatProductDietary();
            $product->formatProductVariant($stateId);
            $product->price = $product->packages->count() == 1 ? $product->packages[0]->statePrice->price : null;
            $product->uniqueurl = $product->uniqueurl;

            return $product;
        });
    }

    public static function getFreeProducts($promotionTypeId)
    {
        $category = '';
        $product = '';
        if ($promotionTypeId == 28) {
            $category = 'Desserts';
        }

        if ($promotionTypeId == 31) {
            $category = 'Beverages';
        }

        if ($promotionTypeId == 35) {
            $category = 'Box Lunches';
        }

        if ($promotionTypeId == 36) {
            $product = 'Cookie box';
        }

        $freeProducts = Product::active();
        if (!empty($category)) {
            $freeProducts
                ->whereHas('category', function ($query) use ($category) {
                    return $query->where('name', 'LIKE', $category)->active();
                })
                ->where('free', 1);
        } else {
            $freeProducts->where('name', 'LIKE', $product);
        }

        return $freeProducts
            ->with([
                'packages' => function ($query) {
                    return $query->with([
                        'options' => function ($query) {
                            return $query
                                ->with(['selections'])
                                ->active()
                                ->orderBy('display_order');
                        },
                    ]);
                },
            ])
            ->get();
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id')->active()->availableInStore();
    }

    public function packages()
    {
        return $this->hasMany(ProductVariant::class, 'product_id')->active()->availableInStore();
    }

    public function options()
    {
        return $this->hasMany(ProductOption::class, 'product_id')->active()->availableInStore();
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class, 'product_id');
    }

    public function addons()
    {
        return $this->belongsToMany(
            Product::class,
            'oj_product_add_ons',
            'product_id',
            'addon_product_id'
        )->availableInStore();
    }

    public function dietary()
    {
        return $this->belongsToMany(Dietary::class, 'oj_product_dietaries', 'product_id', 'dietary_id')
            ->withPivot('type')
            ->whereNull('oj_product_dietaries.deleted_at');
    }

    public function image()
    {
        return $this->hasOne(Image::class, 'entity_id')->where(['entity_type' => self::ENTITY_TYPE]);
    }

    public function uniqueurl()
    {
        return $this->hasOne(UniqueUrl::class, 'entity_id')->where(['entity_type' => self::ENTITY_TYPE]);
    }

    public function item()
    {
        return $this->belongsTo(CartItem::class, 'product_id');
    }

    public function variant()
    {
        return $this->hasMany(ProductVariant::class, 'product_id')->availableInStore(); // ->with(['option.selections']);
    }

    public function availableStore()
    {
        return $this->hasMany('App\Models\FoodAvailableStore', 'entity_id')->where([
            'entity_name' => self::ENTITY_TYPE,
        ]);
    }

    public function fetchProductById($id)
    {
        $id = is_array($id) ? $id : [$id];

        return Product::whereIn('id', $id)->get();
    }

    /**
     * Check if the product belongs to a discount-free category
     *
     * @return bool
     */
    public function isDiscountFree()
    {
        if (!$this->category) {
            return false;
        }

        // Check if the direct category is discount-free
        if ($this->category->is_discount_free) {
            return true;
        }

        return false;
    }
}
