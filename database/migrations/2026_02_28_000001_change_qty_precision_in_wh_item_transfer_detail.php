<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wh_item_transfer_detail', function (Blueprint $table) {
            $table->decimal('qty', 15, 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('wh_item_transfer_detail', function (Blueprint $table) {
            $table->decimal('qty', 15, 4)->change();
        });
    }
};
