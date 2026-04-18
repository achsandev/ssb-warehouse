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
            $table->renameColumn('valuation_methods_id', 'valuation_method_id');
            $table->renameColumn('valuation_methods_name', 'valuation_method_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wh_items', function (Blueprint $table) {
            $table->renameColumn('valuation_methods_id', 'valuation_method_id');
            $table->renameColumn('valuation_methods_name', 'valuation_method_name');
        });
    }
};
