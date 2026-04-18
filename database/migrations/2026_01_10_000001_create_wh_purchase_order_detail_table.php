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
        Schema::create('wh_purchase_order_detail', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('purchase_order_id')->constrained('wh_purchase_order')->cascadeOnDelete();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('unit_id');
            $table->unsignedBigInteger('supplier_id');
            $table->bigInteger('qty');
            $table->bigInteger('price');
            $table->bigInteger('total');
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
        Schema::dropIfExists('wh_purchase_order_detail');
    }
};
