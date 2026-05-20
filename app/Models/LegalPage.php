<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LegalPage extends BaseModel
{
    use SoftDeletes;
    protected $table = 'legal_pages';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(static function (LegalPage $page): void {
            if (!filled($page->uuid)) {
                $page->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }
}
