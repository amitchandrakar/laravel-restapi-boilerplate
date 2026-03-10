<?php

declare(strict_types=1);


namespace App\Models;

class CartOptionsTracks extends BaseModel
{
    protected $connection = 'queue_db'; // Use the queue_db connection

    protected $table = 'cart_options_tracks';

    protected static $unguarded = true;
}
