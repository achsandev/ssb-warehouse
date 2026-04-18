<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master setting formula DPP (Dasar Pengenaan Pajak).
 *
 *  - `name`        : identitas formula, mis. "DPP PPN 11%".
 *  - `formula`     : ekspresi dengan variable `x` (sama seperti Tax Types).
 *                    Di-evaluasi saat transaksi memakai utils mathjs.
 *  - `description` : catatan opsional kapan formula ini dipakai.
 *  - `is_active`   : nonaktifkan tanpa menghapus data historis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wh_setting_dpp_formula', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();

            $table->string('name');
            $table->text('formula');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Audit trail
            $table->unsignedInteger('created_by_id')->nullable();
            $table->string('created_by_name')->nullable();
            $table->unsignedInteger('updated_by_id')->nullable();
            $table->string('updated_by_name')->nullable();

            $table->index('is_active');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wh_setting_dpp_formula');
    }
};
