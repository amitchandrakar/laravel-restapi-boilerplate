<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_gateway_settings', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('gateway', 64)->default('razorpay');
            $table->boolean('is_enabled')->default(false);
            $table->string('environment', 32)->default('sandbox');
            $table->string('live_key_id', 255)->nullable();
            $table->text('live_key_secret')->nullable();
            $table->string('sandbox_key_id', 255)->nullable();
            $table->text('sandbox_key_secret')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->string('currency', 8)->default('INR');
            $table->text('checkout_options_json')->nullable();
            $table->string('webhook_url', 2048)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_settings');
    }
};
