<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wh_item_request', function (Blueprint $table) {
            // Flag: true = request terkait proyek, false = non-proyek.
            // Default true supaya row lama tetap konsisten (sebelumnya selalu
            // menerima project_name, jadi di-assume sebagai project request).
            $table->boolean('is_project')->default(true)->after('warehouse_id');
        });
    }

    public function down(): void
    {
        Schema::table('wh_item_request', function (Blueprint $table) {
            $table->dropColumn('is_project');
        });
    }
};
