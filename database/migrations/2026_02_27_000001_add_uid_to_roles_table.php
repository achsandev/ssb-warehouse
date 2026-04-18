<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('roles', 'uid')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->uuid('uid')->nullable()->unique()->after('id');
            });
        }

        // Backfill uid for existing roles
        DB::table('roles')->whereNull('uid')->orderBy('id')->each(function ($role) {
            DB::table('roles')->where('id', $role->id)->update(['uid' => (string) Str::uuid()]);
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->uuid('uid')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('uid');
        });
    }
};
