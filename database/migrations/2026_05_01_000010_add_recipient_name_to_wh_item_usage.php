<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom `recipient_name` ke `wh_item_usage`.
 *
 * Recipient name = nama orang yang menerima barang (PIC penerima fisik
 * di lapangan). Nullable supaya tidak break data lama dan tetap optional
 * untuk usage internal yang tidak butuh PIC penerima.
 *
 * Diletakkan setelah `project_name` agar urutan kolom natural di DB:
 *   project_name → recipient_name → status.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wh_item_usage', function (Blueprint $table) {
            $table->string('recipient_name', 150)->nullable()->after('project_name');
        });
    }

    public function down(): void
    {
        Schema::table('wh_item_usage', function (Blueprint $table) {
            $table->dropColumn('recipient_name');
        });
    }
};
