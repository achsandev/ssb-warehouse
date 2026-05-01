<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail untuk seluruh event auth.
 *
 * Setiap login berhasil/gagal, logout, session expired, dan akses ditolak
 * akan dicatat di sini. Berguna untuk:
 *   - Forensic: melacak siapa yang akses apa & kapan.
 *   - Detection: identifikasi brute force (banyak login_failed dari 1 IP).
 *   - Compliance: bukti audit trail untuk standar keamanan (ISO 27001 dsb).
 *
 * Tabel sengaja TIDAK di-relate dengan FK ke `users` — kalau user dihapus,
 * audit log tetap intact (data forensik harus immutable). `user_id` cukup
 * disimpan sebagai integer biasa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();

            // Nullable: failed login bisa terjadi sebelum user teridentifikasi.
            $table->unsignedBigInteger('user_id')->nullable();

            // Email yang dipakai saat login (apapun outcome-nya). Berguna
            // untuk trace attempt walau user tidak ada di DB.
            $table->string('email', 191)->nullable();

            // Enum-like string. Pilih varchar (bukan ENUM SQL) supaya event
            // baru bisa ditambah tanpa migration.
            $table->string('event', 50);

            // IPv6 max length = 45 chars. Nullable kalau lewat CLI/queue.
            $table->string('ip_address', 45)->nullable();

            // Browser/client identification. Text karena bisa panjang.
            $table->text('user_agent')->nullable();

            // Konteks tambahan: reason failure, route yang ditolak, dsb.
            $table->json('metadata')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes untuk query yang umum dipakai dashboard/forensic.
            $table->index(['user_id', 'created_at']);
            $table->index(['event', 'created_at']);
            $table->index(['ip_address', 'created_at']);
            $table->index(['email', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_audit_logs');
    }
};
