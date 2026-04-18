<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wh_stock_adjustment_detail', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->unsignedBigInteger('stock_adjustment_id');
            $table->unsignedBigInteger('stock_unit_id');
            $table->unsignedBigInteger('item_id');
            $table->string('item_name', 200);
            $table->unsignedBigInteger('unit_id');
            $table->string('unit_symbol', 30);
            $table->unsignedBigInteger('warehouse_id');
            $table->string('warehouse_name', 150);
            $table->unsignedBigInteger('rack_id')->nullable();
            $table->string('rack_name', 150)->nullable();
            $table->decimal('adjustment_qty', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->string('created_by_name', 150)->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->string('updated_by_name', 150)->nullable();
            $table->timestamps();

            $table->foreign('stock_adjustment_id')
                ->references('id')
                ->on('wh_stock_adjustment')
                ->onDelete('cascade');

            $table->foreign('stock_unit_id')
                ->references('id')
                ->on('wh_stock_units')
                ->onDelete('restrict');

            $table->foreign('item_id')
                ->references('id')
                ->on('wh_items')
                ->onDelete('restrict');

            $table->foreign('unit_id')
                ->references('id')
                ->on('wh_item_units')
                ->onDelete('restrict');

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('wh_warehouse')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wh_stock_adjustment_detail');
    }
};
