<?php

declare(strict_types=1);


namespace App\Models;

class CompanyUser extends BaseModel
{
    protected static $unguarded = true;

    public $timestamps = false;

    protected $table = 'company_users';

    public function companyPayments()
    {
        return $this->hasMany(CompanyPayment::class, 'company_id');
    }
}
