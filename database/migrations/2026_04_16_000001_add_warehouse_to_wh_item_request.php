<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wh_item_request', function (Blueprint $table) {
            $table->unsignedBigInteger('warehouse_id')->nullable()->after('wo_number');
            $table->foreign('warehouse_id')
                ->references('id')->on('wh_warehouse')
                ->nullOnDelete();
        });

        // Backfill existing rows with the first available warehouse, if any.
        $firstWarehouseId = DB::table('wh_warehouse')->orderBy('id')->value('id');
        if ($firstWarehouseId) {
            DB::table('wh_item_request')
                ->whereNull('warehouse_id')
                ->update(['warehouse_id' => $firstWarehouseId]);
        }
    }

    public function down(): void
    {
        Schema::table('wh_item_request', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });
    }
};
