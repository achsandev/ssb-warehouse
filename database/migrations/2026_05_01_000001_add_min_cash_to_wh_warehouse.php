<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom `min_cash` ke `wh_warehouse`.
 *
 * Min cash = ambang minimum kas yang harus dijaga di warehouse. Bila
 * `cash_balance < min_cash`, sistem dapat mem-flag warehouse tersebut
 * sebagai "below minimum" untuk men-trigger Warehouse Cash Request.
 *
 * Nullable + default null → "tidak di-set" / "tidak ada threshold".
 * Decimal(15,2) → presisi rupiah; cap 9.999.999.999.999,99.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wh_warehouse', function (Blueprint $table) {
            $table->decimal('min_cash', 15, 2)->nullable()->after('cash_balance');
        });
    }

    public function down(): void
    {
        Schema::table('wh_warehouse', function (Blueprint $table) {
            $table->dropColumn('min_cash');
        });
    }
};
