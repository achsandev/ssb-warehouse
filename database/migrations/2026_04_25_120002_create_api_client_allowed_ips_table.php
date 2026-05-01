<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * IP allowlist per api_client (CIDR notation).
     *
     * Kalau client tidak punya baris di sini, SEMUA request dari client
     * tersebut akan ditolak 403 oleh middleware `api_client.ip`
     * (fail-closed). Desain ini sengaja ketat karena API ada operasi write.
     */
    public function up(): void
    {
        Schema::create('api_client_allowed_ips', function (Blueprint $table) {
            $table->id();

            $table->foreignId('api_client_id')
                ->constrained('api_clients')
                ->cascadeOnDelete();

            // Simpan dalam format CIDR ("203.0.113.5/32", "10.0.0.0/24").
            // Validasi oleh command/FormRequest; matching memakai
            // Symfony\Component\HttpFoundation\IpUtils::checks().
            $table->string('cidr', 50);
            $table->string('label', 100)->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['api_client_id', 'cidr']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_client_allowed_ips');
    }
};
