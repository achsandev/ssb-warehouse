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
        Schema::create('wh_receipt_item', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('receipt_number')->unique();
            $table->date('receipt_date');
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->char('status', 20);
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
        Schema::dropIfExists('wh_receipt_item');
    }
};
