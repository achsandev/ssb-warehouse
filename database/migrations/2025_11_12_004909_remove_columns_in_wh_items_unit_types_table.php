<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wh_items_unit_types', function (Blueprint $table) {
            $this->dropForeignIfExists('wh_items_unit_types', 'wh_items_unit_types_item_id_foreign');
            $this->dropForeignIfExists('wh_items_unit_types', 'wh_items_unit_types_unit_type_id_foreign');

            // Hapus kolom yang tidak dibutuhkan
            $columns = [
                'id',
                'uid',
                'item_name',
                'unit_type_name',
                'created_at',
                'updated_at',
                'created_by_id',
                'created_by_name',
                'updated_by_id',
                'updated_by_name',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('wh_items_unit_types', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('wh_items_unit_types', function (Blueprint $table) {
            if (! Schema::hasColumn('wh_items_unit_types', 'item_id') ||
                ! Schema::hasColumn('wh_items_unit_types', 'unit_type_id')) {
                throw new \RuntimeException('Columns item_id and unit_type_id must exist before adding composite key.');
            }

            $table->primary(['item_id', 'unit_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wh_items_unit_types', function (Blueprint $table) {
            $table->dropPrimary(['item_id', 'unit_type_id']);
            $table->id();
        });
    }

    /**
     * Utility untuk drop foreign key jika ada.
     */
    private function dropForeignIfExists(string $table, string $foreignKey): void
    {
        $exists = DB::selectOne('
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = ? AND CONSTRAINT_NAME = ?
        ', [$table, $foreignKey]);

        if ($exists) {
            Schema::table($table, function (Blueprint $table) use ($foreignKey) {
                $table->dropForeign($foreignKey);
            });
        }
    }
};
