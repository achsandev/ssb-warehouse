<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('wh_setting_purchase_order_approval', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->unsignedBigInteger('role_id');
            $table->string('role_name');
            $table->unsignedInteger('level')->default(1)->comment('Approval level, lower means earlier approval');
            $table->timestamps();

            // Audit Trail
            $table->unsignedInteger('created_by_id');
            $table->string('created_by_name');
            $table->unsignedInteger('updated_by_id')->nullable();
            $table->string('updated_by_name')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('wh_setting_purchase_order_approval');
    }
};
