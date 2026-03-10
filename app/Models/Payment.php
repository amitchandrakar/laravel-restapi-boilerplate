<?php

declare(strict_types=1);

namespace App\Models;

class Payment extends BaseModel
{
    public function order()
    {
        return $this->hasMany(Order::class, 'payment_id', 'id');
    }

    public function companyPayments()
    {
        return $this->hasMany(CompanyPayment::class, 'payment_id', 'id');
    }
}
