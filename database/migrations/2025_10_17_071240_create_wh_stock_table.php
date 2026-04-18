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
        Schema::create('wh_stock', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->char('code', 7);
            $table->string('name');
            $table->unsignedInteger('brand_id');
            $table->string('brand_name');
            $table->unsignedInteger('item_category_id');
            $table->string('item_category_name');
            $table->unsignedInteger('unit_id');
            $table->string('unit_name');
            $table->string('initial_price');
            $table->string('final_price');
            $table->timestamp('price_updated_at')->nullable();
            $table->unsignedInteger('min_qty');
            $table->unsignedInteger('qty');
            $table->unsignedInteger('warehouse_id');
            $table->string('warehouse_name');
            $table->unsignedInteger('rack_id');
            $table->string('rack_name');
            $table->unsignedInteger('tank_id');
            $table->string('tank_name');
            $table->unsignedInteger('supplier_id');
            $table->string('supplier_name');
            $table->unsignedInteger('usage_unit_id');
            $table->string('usage_unit_name');
            $table->string('part_number');
            $table->string('interchange_part')->nullable();
            $table->text('image');
            $table->date('exp_date')->nullable();
            $table->unsignedInteger('movement_category_id');
            $table->string('movement_category_name');
            $table->unsignedInteger('valuation_methods_id');
            $table->string('valuation_methods_name');
            $table->unsignedInteger('request_type_id');
            $table->string('request_type_name');
            $table->unsignedInteger('material_group_id');
            $table->string('material_group_name');
            $table->unsignedInteger('sub_material_group_id');
            $table->string('sub_material_group_name');
            $table->text('additional_info')->nullable();
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
        Schema::dropIfExists('wh_stock');
    }
};
