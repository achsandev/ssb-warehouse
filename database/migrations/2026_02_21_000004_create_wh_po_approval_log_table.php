<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wh_po_approval_log', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('purchase_order_id')->constrained('wh_purchase_order')->cascadeOnDelete();
            $table->unsignedTinyInteger('approval_level');
            $table->string('role_name');
            $table->enum('status', ['Approved', 'Rejected'])->default('Approved');
            $table->text('notes')->nullable();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('approved_by_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wh_po_approval_log');
    }
};
