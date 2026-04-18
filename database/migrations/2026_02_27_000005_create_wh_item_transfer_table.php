<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wh_item_transfer', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('transfer_number', 30)->unique();
            $table->date('transfer_date');
            $table->unsignedBigInteger('from_warehouse_id');
            $table->string('from_warehouse_name', 150);
            $table->unsignedBigInteger('to_warehouse_id');
            $table->string('to_warehouse_name', 150);
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('Waiting Approval');

            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->string('created_by_name', 150)->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->string('updated_by_name', 150)->nullable();
            $table->timestamps();

            $table->foreign('from_warehouse_id')->references('id')->on('wh_warehouse')->onDelete('restrict');
            $table->foreign('to_warehouse_id')->references('id')->on('wh_warehouse')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wh_item_transfer');
    }
};
