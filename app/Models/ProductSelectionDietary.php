<?php

declare(strict_types=1);


namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSelectionDietary extends BaseModel
{
    use SoftDeletes;

    protected $table = 'oj_product_selection_dietaries';

    public function selection()
    {
        return $this->belongsTo('App\Models\ProductSelection', 'product_selection_id');
    }

    public function dietary()
    {
        return $this->belongsTo('App\Models\Dietary', 'dietary_id');
    }
}
