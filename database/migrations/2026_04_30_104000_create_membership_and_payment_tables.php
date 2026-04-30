<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * packages, subscriptions, user_membership_history, payments, user_payment_history.
 * subscriptions.last_payment_id is not FK-constrained to avoid circular dependency with payments.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('packages')) {
            Schema::create('packages', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('name');
                $table->string('code', 64)->unique();
                $table->text('description')->nullable();
                $table->unsignedInteger('duration_days');
                $table->decimal('price', 14, 2);
                $table->decimal('discounted_price', 14, 2)->nullable();
                $table->string('currency', 8)->default('INR');
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->unsignedBigInteger('created_by')->nullable()->comment('users.id');
                $table->unsignedBigInteger('updated_by')->nullable()->comment('users.id');
                $table->timestamps();
                $table->softDeletes();

                $table->index('is_active');
            });
        }

        if (!Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('package_id')->constrained('packages')->restrictOnDelete();
                $table
                    ->string('subscription_status', 32)
                    ->default('pending')
                    ->comment('active|expired|cancelled|pending');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->boolean('auto_renew')->default(false);
                $table->string('renewal_source', 32)->nullable()->comment('manual|gateway|admin');
                $table->unsignedBigInteger('last_payment_id')->nullable()->comment('payments.id when known; no DB FK.');
                $table->timestamps();

                $table->index(['user_id', 'subscription_status']);
                $table->index('ends_at');
            });
        }

        if (!Schema::hasTable('user_membership_history')) {
            Schema::create('user_membership_history', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('package_id')->constrained('packages')->restrictOnDelete();
                $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
                $table->string('action_type', 32)->comment('started|renewed|upgraded|downgraded|expired|cancelled');
                $table->decimal('amount', 14, 2)->nullable();
                $table->string('currency', 8)->nullable();
                $table->unsignedBigInteger('action_by')->nullable()->comment('users.id or null for system.');
                $table->string('action_source', 32)->comment('user|system|admin');
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['user_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
                $table->foreignId('package_id')->constrained('packages')->restrictOnDelete();
                $table->string('gateway_name', 64)->nullable();
                $table->string('gateway_order_id', 255)->nullable();
                $table->string('gateway_payment_id', 255)->nullable();
                $table->string('gateway_reference_id', 255)->nullable();
                $table->decimal('amount', 14, 2);
                $table->string('currency', 8)->default('INR');
                $table
                    ->string('payment_status', 32)
                    ->default('pending')
                    ->comment('pending|success|failed|refunded|cancelled');
                $table->string('payment_method', 32)->nullable()->comment('upi|card|netbanking|wallet|cash|manual');
                $table->timestamp('paid_at')->nullable();
                $table->text('failed_reason')->nullable();
                $table->json('raw_response_json')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'payment_status']);
                $table->index('paid_at');
            });
        }

        if (!Schema::hasTable('user_payment_history')) {
            Schema::create('user_payment_history', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
                $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
                $table->string('history_type', 32)->comment('initiated|confirmed|failed|refund_initiated|refunded');
                $table->decimal('amount', 14, 2)->nullable();
                $table->string('currency', 8)->nullable();
                $table->text('remarks')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['payment_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_payment_history');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('user_membership_history');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('packages');
    }
};
