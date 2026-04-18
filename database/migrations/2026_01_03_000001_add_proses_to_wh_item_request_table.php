<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wh_item_request', function (Blueprint $table) {
            $table->char('proses', 20)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('wh_item_request', function (Blueprint $table) {
            $table->dropColumn('proses');
        });
    }
};
