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
        Schema::table('wh_items_unit_types', function (Blueprint $table) {
            $table->string('item_name')->nullable()->after('item_id');
            $table->string('unit_type_name')->nullable()->after('unit_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wh_items_unit_types', function (Blueprint $table) {
            $table->dropColumn(['item_name', 'unit_type_name']);
        });
    }
};
