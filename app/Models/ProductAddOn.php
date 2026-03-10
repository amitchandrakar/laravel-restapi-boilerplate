<?php

declare(strict_types=1);


namespace App\Models;

use App\Models\Traits\Scope\CustomScope;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductAddOn extends BaseModel
{
    use CustomScope, SoftDeletes;

    protected $table = 'oj_product_add_ons';
}
