<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wh_stock_opname', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('opname_number', 30)->unique();
            $table->date('opname_date');
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('Draft');

            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->string('created_by_name', 150)->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->string('updated_by_name', 150)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wh_stock_opname');
    }
};
