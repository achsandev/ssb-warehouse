<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat pergerakan stok yang terjadi ketika ItemTransfer di-approve.
 *
 * Desain:
 *  - Terpisah dari `wh_item_transfer_logs` (yang berisi audit transisi status).
 *    Tabel ini khusus mencatat pergerakan stok per-item per-approval: siapa,
 *    kapan, dari lokasi mana ke mana, berapa qty, dengan tipe aksi stok.
 *  - FK ke item_transfer & detail di-set `nullOnDelete` agar riwayat tetap
 *    dipertahankan walaupun dokumen sumber dihapus (immutable audit trail).
 *  - Nama warehouse/rack/tank disimpan sebagai snapshot string supaya laporan
 *    historis tetap akurat meski master berubah (denormalisasi disengaja).
 *  - Index pada `item_id + performed_at` dan `item_transfer_id` untuk query
 *    laporan & drill-down yang umum.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wh_item_transfer_history', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();

            // FK yang tetap ada walau dokumen sumber dihapus (nullOnDelete).
            $table->foreignId('item_transfer_id')
                ->nullable()
                ->constrained('wh_item_transfer')
                ->nullOnDelete();
            $table->foreignId('item_transfer_detail_id')
                ->nullable()
                ->constrained('wh_item_transfer_detail')
                ->nullOnDelete();

            // Item + unit snapshot
            $table->unsignedBigInteger('item_id');
            $table->string('item_name');
            $table->unsignedBigInteger('unit_id');
            $table->string('unit_symbol')->nullable();
            $table->decimal('qty', 18, 4);

            // Source location snapshot
            $table->unsignedBigInteger('from_warehouse_id');
            $table->string('from_warehouse_name');
            $table->unsignedBigInteger('from_rack_id')->nullable();
            $table->string('from_rack_name')->nullable();
            $table->unsignedBigInteger('from_tank_id')->nullable();
            $table->string('from_tank_name')->nullable();

            // Destination location snapshot
            $table->unsignedBigInteger('to_warehouse_id');
            $table->string('to_warehouse_name');
            $table->unsignedBigInteger('to_rack_id')->nullable();
            $table->string('to_rack_name')->nullable();
            $table->unsignedBigInteger('to_tank_id')->nullable();
            $table->string('to_tank_name')->nullable();

            /**
             * Tipe aksi terhadap row wh_stocks:
             *  - relocated : satu row di-update (warehouse/rack/tank diganti),
             *                terjadi saat tujuan belum punya stok item yang sama.
             *  - merged    : qty di-decrement di source, di-increment di destination;
             *                tidak ada row wh_stocks baru yang dibuat.
             */
            $table->enum('action', ['relocated', 'merged']);

            // Audit
            $table->unsignedInteger('performed_by_id')->nullable();
            $table->string('performed_by_name')->nullable();
            $table->timestamp('performed_at');
            $table->timestamps();

            $table->index(['item_transfer_id', 'item_id'], 'ithr_transfer_item_idx');
            $table->index(['item_id', 'performed_at'], 'ithr_item_performed_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wh_item_transfer_history');
    }
};
