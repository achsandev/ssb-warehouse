<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wh_setting_approver_item_request', function (Blueprint $table) {
            $table->string('requester_role_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('wh_setting_approver_item_request', function (Blueprint $table) {
            $table->string('requester_role_name')->nullable(false)->change();
        });
    }
};
