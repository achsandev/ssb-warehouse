<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('wh_item_request_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_id')->nullable()->after('qty');
            $table->string('supplier_name')->nullable()->after('supplier_id');
            $table->decimal('unit_price', 15, 2)->nullable()->after('supplier_name');
            $table->decimal('price', 15, 2)->nullable()->after('unit_price');
        });
    }

    public function down()
    {
        Schema::table('wh_item_request_detail', function (Blueprint $table) {
            $table->dropColumn(['supplier_id', 'supplier_name', 'unit_price', 'price']);
        });
    }
};
