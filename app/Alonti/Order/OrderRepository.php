<?php

declare(strict_types=1);

namespace App\Alonti\Order;

class OrderRepository
{
    public function session()
    {
        return new OrderSession();
    }
}
