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
        Schema::table('wh_receipt_item', function (Blueprint $table) {
            $table->string('project_name')->nullable()->after('receipt_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wh_receipt_item', function (Blueprint $table) {
            $table->dropColumn('project_name');
        });
    }
};
