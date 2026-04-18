<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wh_setting_po_approval', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();

            // Urutan approval (1 = pertama, 2 = kedua, dst)
            $table->unsignedTinyInteger('level');

            // Role yang bertanggung jawab di level ini
            $table->string('role_name');

            // Jika NULL  → level ini SELALU wajib (tidak tergantung total)
            // Jika diisi → level ini hanya aktif ketika total_amount PO >= min_amount
            $table->decimal('min_amount', 15, 2)->nullable();

            // Deskripsi atau keterangan tambahan untuk level ini
            $table->string('description')->nullable();

            // Aktif/nonaktif level tanpa harus menghapus record
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Audit Trail
            $table->unsignedInteger('created_by_id');
            $table->string('created_by_name');
            $table->unsignedInteger('updated_by_id')->nullable();
            $table->string('updated_by_name')->nullable();

            $table->unique('level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wh_setting_po_approval');
    }
};
