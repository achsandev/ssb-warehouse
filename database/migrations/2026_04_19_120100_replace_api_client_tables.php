<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replacement migration untuk redesign ApiClient.
 *
 * Drop infrastruktur lama (CIDR-based IP whitelist + per-request log) dan
 * recreate `api_clients` dengan schema baru yang fokus ke Origin enforcement.
 *
 * Perubahan vs schema lama:
 *   - DROP `api_request_logs`           — audit log dipindah ke `auth_audit_logs`.
 *   - DROP `api_client_allowed_ips`     — IP whitelist diganti Origin check.
 *   - RECREATE `api_clients`            — schema baru tanpa rate_limit/PIC/notes.
 *
 * Token Sanctum di `personal_access_tokens` TIDAK ikut di-drop — token
 * existing tetap valid (kalau ada). Tapi karena `api_clients` di-drop,
 * tokenable_id akan stale. Caller bisa membersihkan token orphan dengan:
 *   `php artisan tinker`
 *   >>> Laravel\Sanctum\PersonalAccessToken::where('tokenable_type', 'App\Models\ApiClient')->delete();
 *
 * down() recreate tabel-tabel lama (tanpa data) supaya rollback technically
 * mungkin — tapi data lama tidak akan kembali.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop dengan urutan FK-safe — child dulu, parent terakhir.
        Schema::dropIfExists('api_request_logs');
        Schema::dropIfExists('api_client_allowed_ips');
        Schema::dropIfExists('api_clients');

        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();

            $table->string('name');
            $table->string('url', 512);
            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('enforce_origin')->default(true);

            $table->timestamps();

            // Audit trail kolom — siapa daftar / ubah client ini.
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->string('created_by_name')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->string('updated_by_name')->nullable();

            $table->index('is_active');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_clients');

        // Recreate skeleton tabel lama untuk reversibility schema (data hilang).
        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('name', 150);
            $table->string('application_url', 255)->nullable();
            $table->string('contact_email', 150)->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('pic_name', 150)->nullable();
            $table->unsignedSmallInteger('rate_limit_per_minute')->default(60);
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('api_client_allowed_ips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_client_id')->constrained('api_clients')->cascadeOnDelete();
            $table->string('cidr', 64);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_client_id')->nullable()->constrained('api_clients')->nullOnDelete();
            $table->string('method', 10);
            $table->string('path');
            $table->unsignedSmallInteger('status_code');
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }
};
