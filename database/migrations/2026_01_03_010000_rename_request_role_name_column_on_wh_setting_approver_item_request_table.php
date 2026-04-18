<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wh_setting_approver_item_request', function (Blueprint $table) {
            $table->renameColumn('request_role_name', 'requester_role_name');
        });
    }

    public function down(): void
    {
        Schema::table('wh_setting_approver_item_request', function (Blueprint $table) {
            $table->renameColumn('requester_role_name', 'request_role_name');
        });
    }
};
