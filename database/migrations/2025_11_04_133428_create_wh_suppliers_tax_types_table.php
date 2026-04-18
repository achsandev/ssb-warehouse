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
        Schema::create('wh_suppliers_tax_types', function (Blueprint $table) {
            $table->foreignId('supplier_id')->constrained('wh_supplier')->cascadeOnDelete();
            $table->foreignId('tax_type_id')->constrained('wh_tax_types')->cascadeOnDelete();
            $table->primary(['supplier_id', 'tax_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wh_suppliers_tax_types');
    }
};
