<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wh_warehouse', function (Blueprint $table) {
            if (! Schema::hasColumn('wh_warehouse', 'cash_balance')) {
                $table->decimal('cash_balance', 15, 2)->default(0)->after('additional_info');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wh_warehouse', function (Blueprint $table) {
            $table->dropColumn('cash_balance');
        });
    }
};
