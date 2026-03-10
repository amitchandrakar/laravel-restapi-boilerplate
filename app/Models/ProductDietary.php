<?php

declare(strict_types=1);


namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class ProductDietary extends BaseModel
{
    use SoftDeletes;

    protected $table = 'oj_product_dietaries';

    public function product()
    {
        return $this->belongsTo('App\Models\Product', 'product_id');
    }

    public function dietary()
    {
        return $this->belongsTo('App\Models\Dietary', 'dietary_id');
    }
}
