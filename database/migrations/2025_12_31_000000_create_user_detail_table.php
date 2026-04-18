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
        Schema::create('user_detail', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('id_karyawan')->nullable();
            $table->string('nik');
            $table->string('name');
            $table->string('department');
            $table->string('sub_department');
            $table->string('position');
            $table->unsignedBigInteger('direct_supervisor_id');
            $table->string('direct_supervisor_position');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('direct_supervisor_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_detail');
    }
};
