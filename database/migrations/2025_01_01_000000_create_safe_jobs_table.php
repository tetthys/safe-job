<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('safe_jobs', function (Blueprint $table): void {
            $table->string('key', 191)->primary(); // IdempotencyKey
            $table->string('state', 32);           // new|running|succeeded|failed

            $table->string('attempt_id', 191)->nullable();
            $table->string('lease_token', 191)->nullable();
            $table->unsignedBigInteger('lease_expires_at_epoch')->nullable();

            $table->timestamp('succeeded_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->string('failure_code', 191)->nullable();
            $table->text('failure_message')->nullable();
            $table->string('failure_category', 191)->nullable();

            $table->json('meta')->nullable(); // optional meta

            $table->timestamps();

            $table->index(['state', 'lease_expires_at_epoch'], 'idx_safe_jobs_state_lease');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safe_jobs');
    }
};
