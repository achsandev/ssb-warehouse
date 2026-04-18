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
            $table->dropColumn(['tax_type_id', 'tax_type']);
            $table->renameColumn('payment_method', 'payment_method_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wh_supplier', function (Blueprint $table) {
            $table->unsignedInteger('tax_type_id')->nullable();
            $table->string('tax_type')->nullable();
            $table->renameColumn('payment_method', 'payment_method_name');
        });
    }
};
