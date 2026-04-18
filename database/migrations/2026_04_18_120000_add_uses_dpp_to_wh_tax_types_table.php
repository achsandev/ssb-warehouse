<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom `uses_dpp` pada wh_tax_types.
 *
 * DPP (Dasar Pengenaan Pajak) = basis perhitungan pajak. Ketika `uses_dpp`
 * bernilai true, perhitungan pajak pada transaksi akan memakai DPP sebagai
 * variable `x`, bukan subtotal langsung. Default false agar tipe pajak lama
 * tidak berubah perilaku.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wh_tax_types', function (Blueprint $table) {
            $table->boolean('uses_dpp')->default(false)->after('formula');
        });
    }

    public function down(): void
    {
        Schema::table('wh_tax_types', function (Blueprint $table) {
            $table->dropColumn('uses_dpp');
        });
    }
};
