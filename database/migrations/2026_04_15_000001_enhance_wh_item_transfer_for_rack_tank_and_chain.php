<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wh_item_transfer', function (Blueprint $table) {
            // Rack & Tank level (opsional)
            $table->unsignedBigInteger('from_rack_id')->nullable()->after('from_warehouse_name');
            $table->string('from_rack_name', 150)->nullable()->after('from_rack_id');
            $table->unsignedBigInteger('from_tank_id')->nullable()->after('from_rack_name');
            $table->string('from_tank_name', 150)->nullable()->after('from_tank_id');

            $table->unsignedBigInteger('to_rack_id')->nullable()->after('to_warehouse_name');
            $table->string('to_rack_name', 150)->nullable()->after('to_rack_id');
            $table->unsignedBigInteger('to_tank_id')->nullable()->after('to_rack_name');
            $table->string('to_tank_name', 150)->nullable()->after('to_tank_id');

            // Cascading / chain transfer
            $table->unsignedBigInteger('parent_transfer_id')->nullable()->after('notes');
            $table->boolean('has_pending_displacement')->default(false)->after('parent_transfer_id');

            // Approval / rejection / cancellation
            $table->text('reject_notes')->nullable()->after('status');
            $table->unsignedBigInteger('approved_by_id')->nullable()->after('reject_notes');
            $table->string('approved_by_name', 150)->nullable()->after('approved_by_id');
            $table->timestamp('approved_at')->nullable()->after('approved_by_name');
            $table->timestamp('cancelled_at')->nullable()->after('approved_at');

            $table->foreign('from_rack_id')->references('id')->on('wh_rack')->onDelete('restrict');
            $table->foreign('from_tank_id')->references('id')->on('wh_tank')->onDelete('restrict');
            $table->foreign('to_rack_id')->references('id')->on('wh_rack')->onDelete('restrict');
            $table->foreign('to_tank_id')->references('id')->on('wh_tank')->onDelete('restrict');
            $table->foreign('parent_transfer_id')->references('id')->on('wh_item_transfer')->onDelete('set null');
        });

        // Audit log — mencatat setiap event pada transfer
        Schema::create('wh_item_transfer_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->unsignedBigInteger('item_transfer_id');
            $table->string('action', 30); // created | approved | rejected | cancelled | revised | stock_moved | displacement_queued
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // flexible payload (delta qty, location changes, etc.)

            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name', 150)->nullable();
            $table->string('actor_role', 150)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('item_transfer_id')->references('id')->on('wh_item_transfer')->onDelete('cascade');
            $table->index(['item_transfer_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wh_item_transfer_logs');

        Schema::table('wh_item_transfer', function (Blueprint $table) {
            $table->dropForeign(['from_rack_id']);
            $table->dropForeign(['from_tank_id']);
            $table->dropForeign(['to_rack_id']);
            $table->dropForeign(['to_tank_id']);
            $table->dropForeign(['parent_transfer_id']);

            $table->dropColumn([
                'from_rack_id', 'from_rack_name', 'from_tank_id', 'from_tank_name',
                'to_rack_id', 'to_rack_name', 'to_tank_id', 'to_tank_name',
                'parent_transfer_id', 'has_pending_displacement',
                'reject_notes', 'approved_by_id', 'approved_by_name',
                'approved_at', 'cancelled_at',
            ]);
        });
    }
};
