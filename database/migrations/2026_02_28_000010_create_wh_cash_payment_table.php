<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wh_cash_payment', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('payment_number', 30)->unique();
            $table->date('payment_date');
            $table->unsignedBigInteger('warehouse_id');
            $table->string('warehouse_name', 150);
            $table->text('description');
            $table->decimal('amount', 15, 2);
            $table->string('spk_path', 500)->nullable();
            $table->string('spk_name', 255)->nullable();
            $table->string('attachment_path', 500)->nullable();
            $table->string('attachment_name', 255)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('Draft');
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->string('created_by_name', 150)->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->string('updated_by_name', 150)->nullable();
            $table->timestamps();

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('wh_warehouse')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wh_cash_payment');
    }
};
