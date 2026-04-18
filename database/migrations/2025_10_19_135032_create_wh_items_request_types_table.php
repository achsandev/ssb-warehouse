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
        Schema::create('wh_items_request_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('wh_items')->cascadeOnDelete();
            $table->string('item_name');
            $table->foreignId('request_type_id')->constrained('wh_request_types');
            $table->string('request_type_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wh_items_request_types');
    }
};
