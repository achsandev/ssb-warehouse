<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ============================
        // 1. Cari nama FK item_id
        // ============================
        $fk = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = 'wh_items_unit_types'
                AND COLUMN_NAME = 'item_id'
                AND REFERENCED_TABLE_NAME IS NOT NULL
                AND CONSTRAINT_SCHEMA = DATABASE()
        ");

        // ============================
        // 2. Drop FK jika ketemu
        // ============================
        if ($fk) {
            DB::statement("ALTER TABLE wh_items_unit_types DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        // ============================
        // 3. Hapus primary key lama (jika ada)
        // ============================
        try {
            DB::statement('ALTER TABLE wh_items_unit_types DROP PRIMARY KEY');
        } catch (\Exception $e) {
            // skip jika tidak ada
        }

        // ============================
        // 4. Tambah composite PK baru
        // ============================
        DB::statement('
            ALTER TABLE wh_items_unit_types
            ADD PRIMARY KEY (item_id, unit_type_id)
        ');

        // ============================
        // 5. Tambah FK baru ON DELETE CASCADE
        // ============================
        DB::statement('
            ALTER TABLE wh_items_unit_types
            ADD CONSTRAINT wh_items_unit_types_item_id_foreign
            FOREIGN KEY (item_id)
            REFERENCES wh_items(id)
            ON DELETE CASCADE
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan perubahan (opsional)
        DB::statement('
            ALTER TABLE wh_items_unit_types
            DROP FOREIGN KEY wh_items_unit_types_item_id_foreign;
        ');

        DB::statement('
            ALTER TABLE wh_items_unit_types
            DROP PRIMARY KEY;
        ');

        // Contoh: fallback ke primary key item_id saja
        DB::statement('
            ALTER TABLE wh_items_unit_types
            ADD PRIMARY KEY (item_id);
        ');
    }
};
