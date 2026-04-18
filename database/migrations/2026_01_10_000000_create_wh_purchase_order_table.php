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
        Schema::create('wh_purchase_order', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('po_number')->unique();
            $table->date('po_date');
            $table->unsignedBigInteger('item_request_id');
            $table->bigInteger('total_amount');
            $table->char('status', 18);
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
        Schema::dropIfExists('wh_purchase_order');
    }
};
