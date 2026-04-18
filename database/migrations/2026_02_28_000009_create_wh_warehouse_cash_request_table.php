<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wh_warehouse_cash_request', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('request_number')->unique();
            $table->date('request_date');
            $table->foreignId('warehouse_id')->constrained('wh_warehouse')->restrictOnDelete();
            $table->string('warehouse_name');
            $table->decimal('amount', 15, 2);
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('Draft');
            $table->timestamps();

            // Audit Trail
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->string('created_by_name')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->string('updated_by_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wh_warehouse_cash_request');
    }
};
