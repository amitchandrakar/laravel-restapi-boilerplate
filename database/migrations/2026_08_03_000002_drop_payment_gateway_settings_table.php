<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('payment_gateway_settings');
    }

    public function down(): void
    {
        // Intentionally empty: gateway settings table was removed with Razorpay.
    }
};
