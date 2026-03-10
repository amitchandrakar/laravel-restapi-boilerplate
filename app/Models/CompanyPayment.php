<?php

declare(strict_types=1);

namespace App\Models;

class CompanyPayment extends BaseModel
{
    protected static $unguarded = true;

    const CREATED_AT = 'created';

    const UPDATED_AT = 'modified';

    protected $table = 'company_payment';

    public function companyUsers()
    {
        return $this->belongsTo(CompanyUser::class, 'company_id');
    }

    public function payments()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }
}
