<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('wh_stock', 'wh_items_services');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('wh_stock', 'wh_items_services');
    }
};
