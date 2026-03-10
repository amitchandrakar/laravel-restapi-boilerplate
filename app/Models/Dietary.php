<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\Attribute\DietaryAttribute;
use App\Models\Traits\Scope\CustomScope;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dietary extends BaseModel
{
    use CustomScope, DietaryAttribute, SoftDeletes;

    protected $table = 'oj_dietaries';

    public static function getDietaries()
    {
        $dietaries = Dietary::active()->get()->pluck('name', 'id')->toArray();

        return [0 => 'No restrictions'] + $dietaries;
    }
}
