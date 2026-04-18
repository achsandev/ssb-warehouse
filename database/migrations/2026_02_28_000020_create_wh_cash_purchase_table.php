<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wh_cash_purchase', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('purchase_number')->unique();
            $table->date('purchase_date');
            $table->unsignedBigInteger('warehouse_id');
            $table->string('warehouse_name');
            $table->unsignedBigInteger('po_id');
            $table->string('po_number');
            $table->decimal('po_total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->char('status', 20)->default('Waiting Approval');
            $table->timestamps();

            // Audit trail
            $table->unsignedInteger('created_by_id');
            $table->string('created_by_name');
            $table->unsignedInteger('updated_by_id')->nullable();
            $table->string('updated_by_name')->nullable();

            $table->foreign('warehouse_id')->references('id')->on('wh_warehouse');
            $table->foreign('po_id')->references('id')->on('wh_purchase_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wh_cash_purchase');
    }
};
