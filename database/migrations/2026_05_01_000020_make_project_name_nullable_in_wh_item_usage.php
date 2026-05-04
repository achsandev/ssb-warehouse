<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Make `project_name` di `wh_item_usage` nullable.
 *
 * Sebelumnya `NOT NULL` — asumsinya setiap usage selalu terkait proyek.
 * Tapi sekarang ItemRequest punya flag `is_project` (boleh false untuk
 * permintaan non-proyek), dan ItemUsage diturunkan dari ItemRequest.
 * Saat ItemRequest non-project (`project_name=null`) di-convert jadi
 * ItemUsage, kolom ini akan dapat null → crash di old schema.
 *
 * Konsisten dengan keputusan migration sebelumnya untuk `wh_item_request`
 * yang juga sudah nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wh_item_usage', function (Blueprint $table) {
            $table->string('project_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Bila rollback diperlukan, baris null harus di-clean dulu manual
        // (mis. update jadi 'Non-Project') sebelum kolom kembali NOT NULL.
        Schema::table('wh_item_usage', function (Blueprint $table) {
            $table->string('project_name')->nullable(false)->change();
        });
    }
};
