<?php

declare(strict_types=1);


namespace App\Models;

use Illuminate\Support\Facades\Cache;

class UniqueUrl extends BaseModel
{
    protected $table = 'oj_unique_urls';

    public function isCategory()
    {
        return $this->entity_type === Category::ENTITY_TYPE;
    }

    public function isProduct()
    {
        return $this->entity_type === Product::ENTITY_TYPE;
    }

    public static function getUniqueUrlByUrl($url)
    {
        return Cache::remember("unique_url:$url", now()->addMinutes(10), function () use ($url) {
            return UniqueUrl::where('url', $url)->first();
        });
    }

    public function categories()
    {
        return $this->belongsTo('App\Models\Category');
    }
}
