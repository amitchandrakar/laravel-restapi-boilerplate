<?php

declare(strict_types=1);

namespace App\Models;

class PaytraceApiLog extends BaseModel
{
    const UPDATED_AT = null;

    protected $table = 'paytrace_api_logs';

    protected static $unguarded = true;
}
