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
        Schema::table('wh_supplier', function (Blueprint $table) {
            $table->unsignedInteger('payment_duration_id')->after('payment_method');
            $table->string('payment_duration_name')->after('payment_duration_id');
            $table->string('npwp_number', 21)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wh_supplier', function (Blueprint $table) {
            $table->dropColumn(['payment_duration_id', 'payment_duration_name']);
            $table->string('npwp_number', 21)->nullable()->change();
        });
    }
};
