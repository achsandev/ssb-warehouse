<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wh_item_request_detail', function (Blueprint $table) {
            $table->dropColumn(['supplier_id', 'supplier_name', 'unit_price', 'price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wh_item_request_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('supplier_name')->nullable();
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->decimal('price', 15, 2)->nullable();
        });
    }
};
