<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItemTransfer\CancelRequest;
use App\Http\Requests\ItemTransfer\RejectRequest;
use App\Http\Requests\ItemTransfer\StoreRequest;
use App\Http\Requests\ItemTransfer\UpdateRequest;
use App\Http\Resources\ItemTransferResource;
use App\Models\Items;
use App\Models\ItemTransfer;
use App\Models\ItemTransferDetail;
use App\Models\ItemTransferHistory;
use App\Models\ItemTransferLog;
use App\Models\ItemUnits;
use App\Models\Rack;
use App\Models\Stock;
use App\Models\StockUnits;
use App\Models\Tank;
use App\Models\Warehouse;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ItemTransferController extends Controller
{
    use ApiResponse;

    // ─── Status constants ────────────────────────────────────────────────────
    private const STATUS_WAITING = 'Waiting Approval';
    private const STATUS_APPROVED = 'Approved';
    private const STATUS_REJECTED = 'Rejected';
    private const STATUS_CANCELLED = 'Cancelled';
    private const STATUS_PENDING_DISPLACEMENT = 'Pending Displacement';

    // ═══════════════════════════════════════════════════════════════════════
    // INDEX / SHOW
    // ═══════════════════════════════════════════════════════════════════════

    public function index(Request $request): JsonResponse
    {
        $page = $request->integer('page', 1);
        $perPage = $request->integer('per_page', 10);
        $sortBy = $request->string('sort_by', 'created_at')->toString();
        $sortDir = $request->string('sort_dir', 'desc')->toString();
        $search = $request->string('search')->toString();

        $allowedSort = [
            'transfer_number', 'transfer_date',
            'from_warehouse_name', 'to_warehouse_name',
            'status', 'created_at',
        ];
        if (! in_array($sortBy, $allowedSort)) $sortBy = 'created_at';

        $query = ItemTransfer::with($this->eagerRelations());

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('transfer_number', 'like', "{$search}%")
                    ->orWhere('from_warehouse_name', 'like', "%{$search}%")
                    ->orWhere('to_warehouse_name', 'like', "%{$search}%");
            });
        }

        // Filter: transfer yang memuat item tertentu (untuk riwayat di halaman Item Detail)
        if ($itemUid = $request->string('item_uid')->toString()) {
            $item = Items::select('id')->where('uid', $itemUid)->first();
            if ($item) {
                $query->whereHas('details', fn ($q) => $q->where('item_id', $item->id));
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $data = $perPage === -1
            ? $query->orderBy($sortBy, $sortDir)->get()
            : $query->orderBy($sortBy, $sortDir)->paginate($perPage, ['*'], 'page', $page);

        return $this->successResponse(
            ItemTransferResource::collection($data),
            'List of item transfers'
        );
    }

    public function show(string $uid): JsonResponse
    {
        $transfer = ItemTransfer::with(array_merge($this->eagerRelations(), [
            'logs', 'childTransfers',
        ]))->where('uid', $uid)->firstOrFail();

        return $this->successResponse(
            new ItemTransferResource($transfer),
            'Detail item transfer'
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // STORE
    // ═══════════════════════════════════════════════════════════════════════

    public function store(StoreRequest $request): JsonResponse
    {
        $transfer = DB::transaction(function () use ($request) {
            $source = $this->resolveLocation(
                $request->input('from_warehouse_uid'),
                $request->input('from_rack_uid'),
                $request->input('from_tank_uid'),
            );
            $dest = $this->resolveLocation(
                $request->input('to_warehouse_uid'),
                $request->input('to_rack_uid'),
                $request->input('to_tank_uid'),
            );

            $parentId = null;
            if ($request->filled('parent_transfer_uid')) {
                $parent = ItemTransfer::where('uid', $request->input('parent_transfer_uid'))->firstOrFail();
                $parentId = $parent->id;
            }

            $transfer = ItemTransfer::create([
                'transfer_date'        => $request->input('transfer_date'),
                'from_warehouse_id'    => $source['warehouse']['id'],
                'from_warehouse_name'  => $source['warehouse']['name'],
                'from_rack_id'         => $source['rack']['id'] ?? null,
                'from_rack_name'       => $source['rack']['name'] ?? null,
                'from_tank_id'         => $source['tank']['id'] ?? null,
                'from_tank_name'       => $source['tank']['name'] ?? null,
                'to_warehouse_id'      => $dest['warehouse']['id'],
                'to_warehouse_name'    => $dest['warehouse']['name'],
                'to_rack_id'           => $dest['rack']['id'] ?? null,
                'to_rack_name'         => $dest['rack']['name'] ?? null,
                'to_tank_id'           => $dest['tank']['id'] ?? null,
                'to_tank_name'         => $dest['tank']['name'] ?? null,
                'notes'                => $request->input('notes'),
                'parent_transfer_id'   => $parentId,
                'status'               => self::STATUS_WAITING,
            ]);

            $this->syncDetails($transfer, $request->input('details'));

            // Jika ini child transfer, mark parent sebagai Pending Displacement
            if ($parentId) {
                ItemTransfer::where('id', $parentId)->update([
                    'has_pending_displacement' => true,
                    'status' => self::STATUS_PENDING_DISPLACEMENT,
                ]);
            }

            $this->logAction($transfer, 'created', null, self::STATUS_WAITING, 'Transfer dibuat.');

            return $transfer;
        });

        return $this->successResponse(
            new ItemTransferResource($transfer->load($this->eagerRelations())),
            'Item transfer created successfully',
            201
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // UPDATE (hanya saat draft/rejected - revisi)
    // ═══════════════════════════════════════════════════════════════════════

    public function update(UpdateRequest $request, string $uid): JsonResponse
    {
        $transfer = ItemTransfer::where('uid', $uid)->firstOrFail();

        if (! in_array($transfer->status, [self::STATUS_WAITING, self::STATUS_REJECTED])) {
            return $this->errorResponse(
                'Transfer yang sudah diproses tidak dapat diubah.',
                ['status' => $transfer->status],
                422
            );
        }

        DB::transaction(function () use ($transfer, $request) {
            $source = $this->resolveLocation(
                $request->input('from_warehouse_uid'),
                $request->input('from_rack_uid'),
                $request->input('from_tank_uid'),
            );
            $dest = $this->resolveLocation(
                $request->input('to_warehouse_uid'),
                $request->input('to_rack_uid'),
                $request->input('to_tank_uid'),
            );

            $previousStatus = $transfer->status;

            $transfer->update([
                'transfer_date'        => $request->input('transfer_date'),
                'from_warehouse_id'    => $source['warehouse']['id'],
                'from_warehouse_name'  => $source['warehouse']['name'],
                'from_rack_id'         => $source['rack']['id'] ?? null,
                'from_rack_name'       => $source['rack']['name'] ?? null,
                'from_tank_id'         => $source['tank']['id'] ?? null,
                'from_tank_name'       => $source['tank']['name'] ?? null,
                'to_warehouse_id'      => $dest['warehouse']['id'],
                'to_warehouse_name'    => $dest['warehouse']['name'],
                'to_rack_id'           => $dest['rack']['id'] ?? null,
                'to_rack_name'         => $dest['rack']['name'] ?? null,
                'to_tank_id'           => $dest['tank']['id'] ?? null,
                'to_tank_name'         => $dest['tank']['name'] ?? null,
                'notes'                => $request->input('notes'),
                'status'               => self::STATUS_WAITING, // revisi kembali ke waiting approval
                'reject_notes'         => null,
            ]);

            $transfer->details()->delete();
            $this->syncDetails($transfer, $request->input('details'));

            $this->logAction(
                $transfer,
                'revised',
                $previousStatus,
                self::STATUS_WAITING,
                'Transfer direvisi dan dikirim ulang untuk persetujuan.'
            );
        });

        return $this->successResponse(
            new ItemTransferResource($transfer->load($this->eagerRelations())),
            'Item transfer updated successfully'
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // APPROVE — validasi stock, pindahkan stock, log audit
    // ═══════════════════════════════════════════════════════════════════════

    public function approve(string $uid): JsonResponse
    {
        $transfer = ItemTransfer::with('details')->where('uid', $uid)->firstOrFail();

        if ($transfer->status !== self::STATUS_WAITING) {
            return $this->errorResponse(
                'Hanya transfer dengan status "Menunggu Persetujuan" yang dapat disetujui.',
                ['status' => $transfer->status],
                422
            );
        }

        // Blokir approve jika ada child transfer yang belum selesai (chain)
        $pendingChildren = ItemTransfer::where('parent_transfer_id', $transfer->id)
            ->whereNotIn('status', [self::STATUS_APPROVED, self::STATUS_CANCELLED])
            ->count();

        if ($pendingChildren > 0) {
            return $this->errorResponse(
                'Transfer ini memiliki displacement transfer yang belum disetujui. Selesaikan transfer terkait terlebih dahulu.',
                ['pending_children' => $pendingChildren],
                422
            );
        }

        // Pre-approval: validasi stok sumber cukup untuk semua detail
        $validation = $this->validateStockAvailability($transfer);
        if ($validation['has_shortage']) {
            return $this->errorResponse(
                'Stok sumber tidak mencukupi untuk beberapa item.',
                ['shortages' => $validation['shortages']],
                422
            );
        }

        DB::transaction(function () use ($transfer) {
            $movements = $this->moveStock($transfer);

            $user = Auth::user();
            $transfer->update([
                'status'           => self::STATUS_APPROVED,
                'reject_notes'     => null,
                'approved_by_id'   => $user?->id,
                'approved_by_name' => $user?->name,
                'approved_at'      => now(),
            ]);

            $this->logAction(
                $transfer,
                'approved',
                self::STATUS_WAITING,
                self::STATUS_APPROVED,
                'Transfer disetujui dan stok telah dipindahkan.',
                ['movements' => $movements]
            );

            // Jika transfer ini adalah child, cek apakah parent bisa lanjut approve
            if ($transfer->parent_transfer_id) {
                $this->tryReleaseParentDisplacement($transfer->parent_transfer_id);
            }
        });

        return $this->successResponse(
            new ItemTransferResource($transfer->fresh($this->eagerRelations())),
            'Item transfer approved successfully'
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // REJECT — wajib sertakan alasan
    // ═══════════════════════════════════════════════════════════════════════

    public function reject(RejectRequest $request, string $uid): JsonResponse
    {
        $transfer = ItemTransfer::where('uid', $uid)->firstOrFail();

        if ($transfer->status !== self::STATUS_WAITING) {
            return $this->errorResponse(
                'Hanya transfer dengan status "Menunggu Persetujuan" yang dapat ditolak.',
                ['status' => $transfer->status],
                422
            );
        }

        DB::transaction(function () use ($transfer, $request) {
            $user = Auth::user();
            $transfer->update([
                'status'           => self::STATUS_REJECTED,
                'reject_notes'     => $request->input('reject_notes'),
                'approved_by_id'   => $user?->id,
                'approved_by_name' => $user?->name,
            ]);

            $this->logAction(
                $transfer,
                'rejected',
                self::STATUS_WAITING,
                self::STATUS_REJECTED,
                $request->input('reject_notes')
            );

            // Jika child transfer ditolak, lepaskan flag pending parent
            if ($transfer->parent_transfer_id) {
                ItemTransfer::where('id', $transfer->parent_transfer_id)->update([
                    'has_pending_displacement' => false,
                    'status' => self::STATUS_REJECTED,
                    'reject_notes' => 'Transfer dibatalkan karena displacement transfer ditolak.',
                ]);
            }
        });

        return $this->successResponse(
            new ItemTransferResource($transfer->fresh($this->eagerRelations())),
            'Item transfer rejected successfully'
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // CANCEL — oleh requester, hanya saat masih Waiting Approval
    // ═══════════════════════════════════════════════════════════════════════

    public function cancel(CancelRequest $request, string $uid): JsonResponse
    {
        $transfer = ItemTransfer::where('uid', $uid)->firstOrFail();

        if (! in_array($transfer->status, [self::STATUS_WAITING, self::STATUS_REJECTED, self::STATUS_PENDING_DISPLACEMENT])) {
            return $this->errorResponse(
                'Transfer ini tidak dapat dibatalkan pada status saat ini.',
                ['status' => $transfer->status],
                422
            );
        }

        DB::transaction(function () use ($transfer, $request) {
            $previousStatus = $transfer->status;
            $transfer->update([
                'status'       => self::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);

            $this->logAction(
                $transfer,
                'cancelled',
                $previousStatus,
                self::STATUS_CANCELLED,
                $request->input('notes') ?: 'Transfer dibatalkan oleh pembuat.'
            );

            // Cancel child transfers juga
            ItemTransfer::where('parent_transfer_id', $transfer->id)
                ->whereIn('status', [self::STATUS_WAITING, self::STATUS_REJECTED])
                ->get()
                ->each(function (ItemTransfer $child) {
                    $child->update(['status' => self::STATUS_CANCELLED, 'cancelled_at' => now()]);
                    $this->logAction(
                        $child,
                        'cancelled',
                        $child->status,
                        self::STATUS_CANCELLED,
                        'Transfer dibatalkan karena parent transfer dibatalkan.'
                    );
                });
        });

        return $this->successResponse(
            new ItemTransferResource($transfer->fresh($this->eagerRelations())),
            'Item transfer cancelled successfully'
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // DESTROY
    // ═══════════════════════════════════════════════════════════════════════

    public function destroy(string $uid): JsonResponse
    {
        $transfer = ItemTransfer::where('uid', $uid)->firstOrFail();

        if ($transfer->status === self::STATUS_APPROVED) {
            return $this->errorResponse(
                'Transfer yang sudah disetujui tidak dapat dihapus (stok sudah dipindahkan).',
                null,
                422
            );
        }

        DB::transaction(function () use ($transfer) {
            $transfer->details()->delete();
            $transfer->delete();
        });

        return $this->successResponse(null, 'Item transfer deleted successfully');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════════════════

    private function eagerRelations(): array
    {
        return [
            'fromWarehouse:id,uid,name',
            'toWarehouse:id,uid,name',
            'fromRack:id,uid,name',
            'toRack:id,uid,name',
            'fromTank:id,uid,name',
            'toTank:id,uid,name',
            'details.item:id,uid,name',
            'details.unit:id,uid,name,symbol',
            'parentTransfer:id,uid,transfer_number,status',
            'childTransfers:id,uid,parent_transfer_id,transfer_number,status',
        ];
    }

    /**
     * Resolve location ids & names berdasarkan uid.
     */
    private function resolveLocation(string $warehouseUid, ?string $rackUid, ?string $tankUid): array
    {
        $warehouse = Warehouse::select('id', 'name')->where('uid', $warehouseUid)->firstOrFail();
        $rack = $rackUid ? Rack::select('id', 'name')->where('uid', $rackUid)->firstOrFail() : null;
        $tank = $tankUid ? Tank::select('id', 'name')->where('uid', $tankUid)->firstOrFail() : null;

        return [
            'warehouse' => ['id' => $warehouse->id, 'name' => $warehouse->name],
            'rack' => $rack ? ['id' => $rack->id, 'name' => $rack->name] : null,
            'tank' => $tank ? ['id' => $tank->id, 'name' => $tank->name] : null,
        ];
    }

    /**
     * Buat detail rows.
     */
    private function syncDetails(ItemTransfer $transfer, array $details): void
    {
        foreach ($details as $detail) {
            $item = Items::select('id', 'name')->where('uid', $detail['item_uid'])->firstOrFail();
            $unit = ItemUnits::select('id', 'name', 'symbol')->where('uid', $detail['unit_uid'])->firstOrFail();

            ItemTransferDetail::create([
                'item_transfer_id' => $transfer->id,
                'item_id'          => $item->id,
                'item_name'        => $item->name,
                'unit_id'          => $unit->id,
                'unit_symbol'      => $unit->symbol,
                'qty'              => $detail['qty'],
                'description'      => $detail['description'] ?? null,
            ]);
        }
    }

    /**
     * Cek ketersediaan stok di lokasi sumber untuk semua detail.
     */
    private function validateStockAvailability(ItemTransfer $transfer): array
    {
        $shortages = [];

        foreach ($transfer->details as $detail) {
            $sourceStock = $this->findStockByLocation(
                $transfer->from_warehouse_id,
                $transfer->from_rack_id,
                $transfer->from_tank_id,
                $detail->item_id,
            );

            $availableQty = 0;
            if ($sourceStock) {
                $su = StockUnits::where('stock_id', $sourceStock->id)
                    ->where('unit_id', $detail->unit_id)
                    ->first();
                $availableQty = $su?->qty ?? 0;
            }

            if ($availableQty < (float) $detail->qty) {
                $shortages[] = [
                    'item_name'     => $detail->item_name,
                    'requested_qty' => (float) $detail->qty,
                    'available_qty' => (float) $availableQty,
                    'unit_symbol'   => $detail->unit_symbol,
                ];
            }
        }

        return [
            'has_shortage' => count($shortages) > 0,
            'shortages'    => $shortages,
        ];
    }

    /**
     * Pindahkan stok dari source ke destination.
     *
     * Kontrak: NO new row insert di `wh_stocks`. Dua strategi:
     *  1. MERGED   — jika destination stock sudah ada untuk item yang sama,
     *                qty di-decrement di source dan di-increment di destination.
     *                Tidak ada row wh_stocks baru. (StockUnits boleh dibuat
     *                bila unit belum ada di destination, karena tabel berbeda.)
     *  2. RELOCATED — jika destination belum punya stock untuk item tsb,
     *                 row source di-update (warehouse/rack/tank diganti ke tujuan).
     *                 Row tetap sama, hanya lokasinya yang berubah.
     *                 Hanya diperbolehkan saat qty yang dipindahkan = seluruh qty
     *                 di source untuk unit tsb (full move).
     *
     * Setiap pergerakan dicatat ke `wh_item_transfer_history` sebagai audit trail.
     *
     * @throws \DomainException  Jika data tidak konsisten (source kosong / partial move tanpa destination).
     * @return array<int, array<string,mixed>>  Ringkasan movement untuk log transfer utama.
     */
    private function moveStock(ItemTransfer $transfer): array
    {
        $movements = [];
        $user      = Auth::user();
        $historyBatch = [];

        foreach ($transfer->details as $detail) {
            $sourceStock = $this->findStockByLocation(
                $transfer->from_warehouse_id,
                $transfer->from_rack_id,
                $transfer->from_tank_id,
                $detail->item_id,
            );

            if (! $sourceStock) {
                throw new \DomainException(
                    "Stok sumber tidak ditemukan untuk item '{$detail->item_name}' di lokasi asal."
                );
            }

            $destStock = $this->findStockByLocation(
                $transfer->to_warehouse_id,
                $transfer->to_rack_id,
                $transfer->to_tank_id,
                $detail->item_id,
            );

            // Guard: source & destination identik — invalid.
            if ($destStock && $destStock->id === $sourceStock->id) {
                throw new \DomainException(
                    "Lokasi asal dan tujuan sama untuk item '{$detail->item_name}'."
                );
            }

            $action = $destStock
                ? $this->mergeIntoExistingDestination($sourceStock, $destStock, $detail)
                : $this->relocateSourceToDestination($sourceStock, $transfer, $detail);

            $historyBatch[] = [
                'uid'                     => (string) Str::uuid(),
                'item_transfer_id'        => $transfer->id,
                'item_transfer_detail_id' => $detail->id,
                'item_id'                 => $detail->item_id,
                'item_name'               => $detail->item_name,
                'unit_id'                 => $detail->unit_id,
                'unit_symbol'             => $detail->unit_symbol,
                'qty'                     => $detail->qty,
                'from_warehouse_id'       => $transfer->from_warehouse_id,
                'from_warehouse_name'     => $transfer->from_warehouse_name,
                'from_rack_id'            => $transfer->from_rack_id,
                'from_rack_name'          => $transfer->from_rack_name,
                'from_tank_id'            => $transfer->from_tank_id,
                'from_tank_name'          => $transfer->from_tank_name,
                'to_warehouse_id'         => $transfer->to_warehouse_id,
                'to_warehouse_name'       => $transfer->to_warehouse_name,
                'to_rack_id'              => $transfer->to_rack_id,
                'to_rack_name'            => $transfer->to_rack_name,
                'to_tank_id'              => $transfer->to_tank_id,
                'to_tank_name'            => $transfer->to_tank_name,
                'action'                  => $action,
                'performed_by_id'         => $user?->id,
                'performed_by_name'       => $user?->name,
                'performed_at'            => now(),
                'created_at'              => now(),
                'updated_at'              => now(),
            ];

            $movements[] = [
                'item_name'       => $detail->item_name,
                'unit_symbol'     => $detail->unit_symbol,
                'qty'             => (float) $detail->qty,
                'source_stock_id' => $sourceStock->id,
                'dest_stock_id'   => $destStock?->id ?? $sourceStock->id, // setelah relocate, source row jadi destination
                'action'          => $action,
            ];
        }

        // Batch insert — lebih efisien daripada insert per-loop.
        if ($historyBatch !== []) {
            ItemTransferHistory::insert($historyBatch);
        }

        return $movements;
    }

    /**
     * Strategi MERGED: tujuan sudah punya stock row untuk item tsb.
     * Decrement source qty, increment destination qty. Tidak create row wh_stocks.
     */
    private function mergeIntoExistingDestination(Stock $sourceStock, Stock $destStock, ItemTransferDetail $detail): string
    {
        // Decrement source — lockForUpdate untuk mencegah race.
        $sourceUnit = StockUnits::where('stock_id', $sourceStock->id)
            ->where('unit_id', $detail->unit_id)
            ->lockForUpdate()
            ->first();

        if (! $sourceUnit || (float) $sourceUnit->qty < (float) $detail->qty) {
            throw new \DomainException(
                "Qty sumber tidak mencukupi untuk item '{$detail->item_name}'."
            );
        }

        $sourceUnit->decrement('qty', $detail->qty);

        // Increment destination; StockUnits boleh di-create bila row unit belum ada
        // (tabel StockUnits bukan wh_stocks — tidak melanggar aturan).
        $destUnit = StockUnits::where('stock_id', $destStock->id)
            ->where('unit_id', $detail->unit_id)
            ->lockForUpdate()
            ->first();

        if ($destUnit) {
            $destUnit->increment('qty', $detail->qty);
        } else {
            StockUnits::create([
                'stock_id' => $destStock->id,
                'item_id'  => $detail->item_id,
                'unit_id'  => $detail->unit_id,
                'qty'      => $detail->qty,
            ]);
        }

        return ItemTransferHistory::ACTION_MERGED;
    }

    /**
     * Strategi RELOCATED: tujuan belum punya stock row — pindahkan source row
     * dengan UPDATE warehouse/rack/tank ke lokasi tujuan. Qty tidak berubah.
     *
     * Hanya valid saat detail qty == total qty source untuk unit tsb (full move).
     * Partial move tanpa destination existing akan ditolak (user melarang insert baru).
     */
    private function relocateSourceToDestination(Stock $sourceStock, ItemTransfer $transfer, ItemTransferDetail $detail): string
    {
        $totalSourceQty = (float) StockUnits::where('stock_id', $sourceStock->id)
            ->where('unit_id', $detail->unit_id)
            ->lockForUpdate()
            ->sum('qty');

        if ($totalSourceQty <= 0) {
            throw new \DomainException(
                "Stok sumber kosong untuk item '{$detail->item_name}'."
            );
        }

        if ((float) $detail->qty < $totalSourceQty) {
            throw new \DomainException(
                "Transfer parsial tidak dapat diproses karena belum ada stok tujuan untuk item "
                . "'{$detail->item_name}'. Pindahkan seluruh qty atau buat stok tujuan terlebih dahulu."
            );
        }

        $sourceStock->update([
            'warehouse_id'   => $transfer->to_warehouse_id,
            'warehouse_name' => $transfer->to_warehouse_name,
            'rack_id'        => $transfer->to_rack_id,
            'rack_name'      => $transfer->to_rack_name,
            'tank_id'        => $transfer->to_tank_id,
            'tank_name'      => $transfer->to_tank_name,
        ]);

        return ItemTransferHistory::ACTION_RELOCATED;
    }

    /**
     * Cari record Stock yang sesuai dengan lokasi (rak/tangki jika ada).
     */
    private function findStockByLocation(int $warehouseId, ?int $rackId, ?int $tankId, int $itemId): ?Stock
    {
        return Stock::where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->when($rackId, fn ($q) => $q->where('rack_id', $rackId), fn ($q) => $q->whereNull('rack_id'))
            ->when($tankId, fn ($q) => $q->where('tank_id', $tankId), fn ($q) => $q->whereNull('tank_id'))
            ->lockForUpdate()
            ->first();
    }

    /**
     * Setelah child transfer approved, coba cek apakah parent bisa lepas dari pending.
     */
    private function tryReleaseParentDisplacement(int $parentId): void
    {
        $parent = ItemTransfer::find($parentId);
        if (! $parent) return;

        $stillPending = ItemTransfer::where('parent_transfer_id', $parentId)
            ->whereNotIn('status', [self::STATUS_APPROVED, self::STATUS_CANCELLED])
            ->exists();

        if (! $stillPending && $parent->status === self::STATUS_PENDING_DISPLACEMENT) {
            $parent->update([
                'has_pending_displacement' => false,
                'status' => self::STATUS_WAITING,
            ]);

            $this->logAction(
                $parent,
                'displacement_cleared',
                self::STATUS_PENDING_DISPLACEMENT,
                self::STATUS_WAITING,
                'Semua displacement transfer selesai. Siap untuk disetujui.'
            );
        }
    }

    /**
     * Catat audit log untuk transfer.
     */
    private function logAction(
        ItemTransfer $transfer,
        string $action,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $notes,
        ?array $metadata = null,
    ): void {
        $user = Auth::user();

        ItemTransferLog::create([
            'item_transfer_id' => $transfer->id,
            'action'           => $action,
            'from_status'      => $fromStatus,
            'to_status'        => $toStatus,
            'notes'            => $notes,
            'metadata'         => $metadata,
            'actor_id'         => $user?->id,
            'actor_name'       => $user?->name,
            'actor_role'       => $user?->roles?->first()?->name,
        ]);
    }
}
