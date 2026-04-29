<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('oj_products', function (Blueprint $table) {
            $table->unsignedTinyInteger('is_featured')->default(0);
        });

        // Mark first 5 products by display_order as featured for the homepage section
        $featuredIds = DB::table('oj_products')
            ->whereNull('deleted_at')
            ->orderBy('display_order')
            ->orderBy('id')
            ->limit(5)
            ->pluck('id');

        if ($featuredIds->isNotEmpty()) {
            DB::table('oj_products')
                ->whereIn('id', $featuredIds)
                ->update(['is_featured' => 1]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oj_products', function (Blueprint $table) {
            $table->dropColumn('is_featured');
        });
    }
};
