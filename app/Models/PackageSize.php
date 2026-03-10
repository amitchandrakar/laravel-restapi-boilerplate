<?php

declare(strict_types=1);


namespace App\Models;

use App\Models\Traits\Scope\CustomScope;
use Illuminate\Database\Eloquent\SoftDeletes;

class PackageSize extends BaseModel
{
    use CustomScope, SoftDeletes;

    const ENTITY_TYPE = 'OjPackageSizes';

    protected $table = 'oj_package_sizes';

    public function statePrice()
    {
        return $this->hasOne(StatePrice::class, 'entity_id')->where(['entity_type' => self::ENTITY_TYPE]);
    }
}
