<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds option_id to oj_product_selections so options can have many selections
     * via a direct FK (oj_product_selections.option_id = oj_product_options.id).
     */
    public function up(): void
    {
        Schema::table('oj_product_selections', function (Blueprint $table) {
            $table->unsignedInteger('option_id')->nullable()->after('id');
            $table->foreign('option_id')
                ->references('id')
                ->on('oj_product_options')
                ->onUpdate('cascade')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oj_product_selections', function (Blueprint $table) {
            $table->dropForeign(['option_id']);
            $table->dropColumn('option_id');
        });
    }
};
