<?php

declare(strict_types=1);


namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class ProductOptionSelection extends BaseModel
{
    use SoftDeletes;

    protected $table = 'oj_product_option_selections';

    public function option()
    {
        return $this->belongsTo(ProductOption::class, 'product_option_id');
    }

    public function selection()
    {
        return $this->belongsTo(ProductSelection::class, 'product_selection_id');
    }
}
