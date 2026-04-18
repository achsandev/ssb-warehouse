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
        Schema::create('wh_supplier', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('name');
            $table->string('address');
            $table->string('phone_number', 15);
            $table->string('npwp_number', 20);
            $table->string('pic_name');
            $table->string('email');
            $table->unsignedInteger('payment_method_id');
            $table->string('payment_method');
            $table->unsignedInteger('tax_type_id');
            $table->string('tax_type');
            $table->text('additional_info')->nullable();
            $table->timestamps();

            // Audit Trail
            $table->unsignedInteger('created_by_id');
            $table->string('created_by_name');
            $table->unsignedInteger('updated_by_id')->nullable();
            $table->string('updated_by_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wh_supplier');
    }
};
