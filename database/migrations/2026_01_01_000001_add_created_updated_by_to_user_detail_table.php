<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by_id')->nullable()->after('updated_at');
            $table->string('created_by_name')->nullable()->after('created_by_id');
            $table->unsignedBigInteger('updated_by_id')->nullable()->after('created_by_name');
            $table->string('updated_by_name')->nullable()->after('updated_by_id');
        });
    }

    public function down(): void
    {
        Schema::table('user_detail', function (Blueprint $table) {
            $table->dropColumn(['created_by_id', 'created_by_name', 'updated_by_id', 'updated_by_name']);
        });
    }
};
