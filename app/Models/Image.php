<?php

declare(strict_types=1);


namespace App\Models;

use App\Models\Traits\Attribute\ImageAttribute;
use Illuminate\Database\Eloquent\SoftDeletes;

class Image extends BaseModel
{
    use ImageAttribute, SoftDeletes;

    protected $table = 'oj_images';

    public static function getBasePath($value)
    {
        $folders = [
            config('custom.s3.url'),
            config('custom.s3.folder'),
            strtolower($value->entity_type),
            $value->entity_id,
        ];

        return implode('/', $folders);
    }

    public function category()
    {
        return $this->belongsTo(Category::class)->where(['entity_type' => 'OjCategories']);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->where(['entity_type' => 'OjProducts']);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class)->where(['entity_type' => 'OjProductVariants']);
    }
}
