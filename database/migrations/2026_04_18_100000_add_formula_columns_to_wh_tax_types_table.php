<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom formula_type (enum) dan formula (text) pada wh_tax_types.
 *
 *  - formula_type : 'formula' | 'percentage' | 'manual'. Default 'percentage'
 *                   (tipe pajak paling umum — mis. PPN 11%).
 *  - formula      : rumus/ekspresi dalam bentuk bebas (mis. "price * 0.11" atau
 *                   "manual input"). Nullable karena tidak semua tipe butuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wh_tax_types', function (Blueprint $table) {
            $table->enum('formula_type', ['formula', 'percentage', 'manual'])
                ->default('percentage')
                ->after('description');
            $table->text('formula')->nullable()->after('formula_type');
        });
    }

    public function down(): void
    {
        Schema::table('wh_tax_types', function (Blueprint $table) {
            $table->dropColumn(['formula_type', 'formula']);
        });
    }
};
