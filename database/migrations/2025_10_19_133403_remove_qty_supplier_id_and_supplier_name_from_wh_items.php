<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wh_items', function (Blueprint $table) {
            $table->dropColumn(['qty', 'supplier_id', 'supplier_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wh_items', function (Blueprint $table) {
            $table->unsignedInteger('qty')->nullable();
            $table->unsignedInteger('supplier_id')->nullable();
            $table->string('supplier_name')->nullable();
        });
    }
};
