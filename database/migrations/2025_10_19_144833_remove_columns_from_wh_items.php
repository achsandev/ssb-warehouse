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
            $table->dropColumn([
                'warehouse_id',
                'warehouse_name',
                'rack_id',
                'rack_name',
                'tank_id',
                'tank_name',
                'usage_unit_id',
                'usage_unit_name',
                'request_type_id',
                'request_type_name',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wh_items', function (Blueprint $table) {
            $table->unsignedInteger('warehouse_id')->nullable();
            $table->string('warehouse_name')->nullable();
            $table->unsignedInteger('rack_id')->nullable();
            $table->string('rack_name')->nullable();
            $table->unsignedInteger('tank_id')->nullable();
            $table->string('tank_name')->nullable();
            $table->unsignedInteger('usage_unit_id')->nullable();
            $table->string('usage_unit_name')->nullable();
            $table->unsignedInteger('request_type_id')->nullable();
            $table->string('request_type_name')->nullable();
        });
    }
};
