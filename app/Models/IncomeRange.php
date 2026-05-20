<?php

declare(strict_types=1);

namespace App\Models;

class IncomeRange extends BaseModel
{
    protected $table = 'income_ranges';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
