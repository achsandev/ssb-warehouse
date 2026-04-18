<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wh_setting_po_approval', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->after('level');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
        });

        Schema::table('wh_setting_po_approval', function (Blueprint $table) {
            $table->dropColumn('role_name');
        });
    }

    public function down(): void
    {
        Schema::table('wh_setting_po_approval', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
            $table->string('role_name')->after('level');
        });
    }
};
