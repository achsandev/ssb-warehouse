<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wh_item_transfer_detail', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->unsignedBigInteger('item_transfer_id');
            $table->unsignedBigInteger('item_id');
            $table->string('item_name', 200);
            $table->unsignedBigInteger('unit_id');
            $table->string('unit_symbol', 30);
            $table->decimal('qty', 15, 4);
            $table->text('description')->nullable();

            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->string('created_by_name', 150)->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->string('updated_by_name', 150)->nullable();
            $table->timestamps();

            $table->foreign('item_transfer_id')->references('id')->on('wh_item_transfer')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('wh_items')->onDelete('restrict');
            $table->foreign('unit_id')->references('id')->on('wh_item_units')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wh_item_transfer_detail');
    }
};
