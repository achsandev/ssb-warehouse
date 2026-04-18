<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wh_setting_approver_item_request', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->unsignedBigInteger('requester_role_id'); // Role yang mengajukan
            $table->string('request_role_name');
            $table->unsignedBigInteger('approver_role_id'); // Role yang menyetujui
            $table->string('approver_role_name');
            $table->timestamps();

            $table->foreign('requester_role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('approver_role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->unique(['requester_role_id', 'approver_role_id'], 'unique_requester_approver');

            // Audit Trail
            $table->unsignedInteger('created_by_id');
            $table->string('created_by_name');
            $table->unsignedInteger('updated_by_id')->nullable();
            $table->string('updated_by_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wh_setting_approver_item_request');
    }
};
