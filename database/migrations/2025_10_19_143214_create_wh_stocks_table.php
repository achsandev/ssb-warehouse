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
        Schema::create('wh_stocks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('item_id')->constrained('wh_items')->cascadeOnDelete();
            $table->string('item_name');
            $table->foreignId('warehouse_id')->constrained('wh_warehouse');
            $table->string('warehouse_name');
            $table->foreignId('rack_id')->nullable()->constrained('wh_rack');
            $table->string('rack_name')->nullable();
            $table->foreignId('tank_id')->nullable()->constrained('wh_tank');
            $table->string('tank_name')->nullable();
            $table->integer('qty');
            $table->foreignId('unit_id')->constrained('wh_item_units');
            $table->char('unit_symbol', 5);
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
        Schema::dropIfExists('wh_stocks');
    }
};
