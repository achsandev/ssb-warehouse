<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_detail', function (Blueprint $table) {
            $table->dropForeign(['direct_supervisor_id']);
        });
    }

    public function down(): void
    {
        Schema::table('user_detail', function (Blueprint $table) {
            $table->foreign('direct_supervisor_id')->references('id')->on('users');
        });
    }
};
