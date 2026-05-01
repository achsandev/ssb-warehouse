<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master aplikasi partner B2B yang diizinkan mengakses API.
     *
     * Tabel ini menjadi `tokenable` dari `personal_access_tokens` Sanctum —
     * artinya token BUKAN milik user manusia, tapi milik "aplikasi partner".
     * Dengan begitu audit/revoke/rate-limit bisa dilakukan per-aplikasi.
     */
    public function up(): void
    {
        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();

            $table->string('name', 150);
            $table->string('application_url', 255)->nullable()
                ->comment('Informasi saja — URL aplikasi partner. Tidak dipakai untuk validasi request.');

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
    }

    public function down(): void
    {
        Schema::dropIfExists('api_clients');
    }
};
