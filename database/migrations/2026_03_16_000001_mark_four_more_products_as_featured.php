<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mark 4 more products as featured (is_featured = 1) so there are 6 total.
     */
    public function up(): void
    {
        $ids = DB::table('oj_products')
            ->where('is_featured', '!=', 1)
            ->whereNull('deleted_at')
            ->orderBy('display_order')
            ->orderBy('id')
            ->limit(4)
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            DB::table('oj_products')
                ->whereIn('id', $ids)
                ->update(['is_featured' => 1]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally un-feature the same 4 products (by id we'd need to store them).
        // For simplicity we leave featured flags as-is on rollback.
    }
};
