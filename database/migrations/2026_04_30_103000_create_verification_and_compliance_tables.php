<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * user_verification_documents, deleted_accounts, data_erasure_requests, otp_requests.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('user_verification_documents')) {
            Schema::create('user_verification_documents', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('document_type', 32)->comment('aadhaar|pan|passport|voter_id|driving_license|other');
                $table->string('document_number_masked', 255)->nullable();
                $table->string('document_front_url', 2048)->nullable();
                $table->string('document_back_url', 2048)->nullable();
                $table->string('selfie_url', 2048)->nullable();
                $table->string('verification_status', 32)->default('pending')->comment('pending|approved|rejected|resubmission_required');
                $table->unsignedBigInteger('verified_by')->nullable()->comment('users.id of reviewer.');
                $table->timestamp('verified_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'verification_status']);
            });
        }

        if (!Schema::hasTable('deleted_accounts')) {
            Schema::create('deleted_accounts', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('reason', 128)->nullable();
                $table->text('reason_notes')->nullable();
                $table->boolean('deleted_by_user')->default(false);
                $table->boolean('deleted_by_admin')->default(false);
                $table->string('deleted_ip', 45)->nullable();
                $table->text('deleted_user_agent')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index('user_id');
            });
        }

        if (!Schema::hasTable('data_erasure_requests')) {
            Schema::create('data_erasure_requests', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('request_type', 32)->comment('soft_delete|hard_delete|anonymize');
                $table->string('status', 32)->default('requested')->comment('requested|in_review|completed|rejected');
                $table->timestamp('requested_at')->useCurrent();
                $table->unsignedBigInteger('processed_by')->nullable()->comment('users.id of processor.');
                $table->timestamp('processed_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
            });
        }

        if (!Schema::hasTable('otp_requests')) {
            Schema::create('otp_requests', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('channel', 16)->comment('sms|email|whatsapp');
                $table->string('destination', 255);
                $table->string('otp_hash', 255);
                $table->string('purpose', 32)->comment('register|login|phone_verify|email_verify|password_reset');
                $table->unsignedSmallInteger('attempt_count')->default(0);
                $table->unsignedSmallInteger('max_attempts')->default(5);
                $table->string('status', 32)->default('sent')->comment('sent|verified|expired|failed|blocked');
                $table->timestamp('requested_at')->useCurrent();
                $table->timestamp('expires_at');
                $table->timestamp('verified_at')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['destination', 'purpose', 'status']);
                $table->index('expires_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_requests');
        Schema::dropIfExists('data_erasure_requests');
        Schema::dropIfExists('deleted_accounts');
        Schema::dropIfExists('user_verification_documents');
    }
};
