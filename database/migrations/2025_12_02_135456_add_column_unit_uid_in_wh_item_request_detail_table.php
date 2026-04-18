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
        Schema::table('wh_item_request_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->after('item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wh_item_request_detail', function (Blueprint $table) {
            $table->dropColumn('unit_uid');
        });
    }
};
