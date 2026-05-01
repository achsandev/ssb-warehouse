<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit log request API masuk.
     *
     * Tidak menyimpan body mentah — hanya SHA-256 hash body untuk audit
     * tamper-evidence. Retention 90 hari, di-prune otomatis oleh scheduler
     * command `api:prune-logs`.
     */
    public function up(): void
    {
        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('api_client_id')->nullable()->index();
            $table->unsignedBigInteger('token_id')->nullable();

            $table->string('method', 10);
            $table->string('path', 255);
            $table->string('ip', 45); // cukup untuk IPv6

            $table->unsignedSmallInteger('status_code');
            $table->unsignedInteger('response_time_ms')->nullable();

            $table->string('request_body_hash', 64)->nullable()
                ->comment('SHA-256 of raw request body. NULL jika body kosong.');
            $table->string('user_agent', 255)->nullable();

            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['api_client_id', 'created_at']);
            $table->index(['status_code', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_request_logs');
    }
};
