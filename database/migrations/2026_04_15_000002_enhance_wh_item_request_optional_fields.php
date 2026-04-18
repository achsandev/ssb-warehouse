<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wh_item_request', function (Blueprint $table) {
            // Field baru (optional)
            $table->string('unit_code', 100)->nullable()->after('request_date');
            $table->string('wo_number', 100)->nullable()->after('unit_code');

            // project_name dibuat nullable (tidak wajib)
            $table->string('project_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('wh_item_request', function (Blueprint $table) {
            $table->dropColumn(['unit_code', 'wo_number']);
            // Kembalikan project_name ke NOT NULL (kalau ada data null, perlu handle manual)
            $table->string('project_name')->nullable(false)->change();
        });
    }
};
