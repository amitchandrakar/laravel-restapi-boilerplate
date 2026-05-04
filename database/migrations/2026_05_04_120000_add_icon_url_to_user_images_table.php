<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_images', function (Blueprint $table): void {
            $table->string('icon_url', 2048)->nullable()->after('thumbnail_url');
        });
    }

    public function down(): void
    {
        Schema::table('user_images', function (Blueprint $table): void {
            $table->dropColumn('icon_url');
        });
    }
};
