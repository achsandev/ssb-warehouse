<?php

namespace App\Http\Controllers;

use App\Http\Requests\WarehouseCashRequest\StoreRequest;
use App\Http\Requests\WarehouseCashRequest\UpdateRequest;
use App\Http\Resources\WarehouseCashRequestResource;
use App\Models\Warehouse;
use App\Helpers\FileHelper;
use App\Models\WarehouseCashRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class WarehouseCashRequestController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $page = $request->integer('page', 1);
        $perPage = $request->integer('per_page', 10);
        $sortBy = $request->string('sort_by', 'created_at')->toString();
        $sortDir = $request->string('sort_dir', 'desc')->toString();
        $search = $request->string('search')->toString();

        $allowedSort = ['request_number', 'request_date', 'warehouse_name', 'amount', 'status', 'created_at'];
        if (! in_array($sortBy, $allowedSort)) {
            $sortBy = 'created_at';
        }

        $columns = [
            'id', 'uid', 'request_number', 'request_date',
            'warehouse_id', 'warehouse_name', 'amount',
            'attachment_path', 'attachment_name',
            'notes', 'status',
            'created_at', 'updated_at', 'created_by_name', 'updated_by_name',
        ];

        $query = WarehouseCashRequest::with(['warehouse:id,uid,name,cash_balance']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "{$search}%")
                    ->orWhere('warehouse_name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($perPage === -1) {
            $data = $query->orderBy($sortBy, $sortDir)->get($columns);

            return $this->successResponse(
                WarehouseCashRequestResource::collection($data),
                'List of warehouse cash requests'
            );
        }

        $data = $query->orderBy($sortBy, $sortDir)->paginate($perPage, $columns, 'page', $page);

        return $this->successResponse(
            WarehouseCashRequestResource::collection($data),
            'List of warehouse cash requests'
        );
    }

    public function show(string $uid): JsonResponse
    {
        $record = WarehouseCashRequest::with(['warehouse:id,uid,name,cash_balance'])
            ->where('uid', $uid)
            ->firstOrFail();

        return $this->successResponse(
            new WarehouseCashRequestResource($record),
            'Detail warehouse cash request'
        );
    }

    /**
     * Lookup: list warehouses with their current cash balance (for the create form).
     */
    public function warehousesLookup(): JsonResponse
    {
        $warehouses = Warehouse::select('id', 'uid', 'name', 'cash_balance')
            ->orderBy('name')
            ->get()
            ->map(fn ($w) => [
                'uid' => $w->uid,
                'name' => $w->name,
                'cash_balance' => (float) $w->cash_balance,
            ]);

        return $this->successResponse($warehouses, 'List of warehouses');
    }

    public function store(StoreRequest $request): JsonResponse
    {
        $warehouse = Warehouse::where('uid', $request->input('warehouse_uid'))->firstOrFail();

        abort_if(
            $warehouse->cash_balance >= 1_000_000,
            422,
            "Permintaan kas tidak dapat dilakukan. Saldo kas gudang \"{$warehouse->name}\" masih mencukupi (≥ Rp 1.000.000)."
        );

        $record = DB::transaction(function () use ($request, $warehouse) {
            $attachmentPath = null;
            $attachmentName = null;

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileHelper = new FileHelper;
                $fileName = $fileHelper->generateFileName($file);
                $attachmentName = $file->getClientOriginalName();
                $attachmentPath = $file->storeAs('warehouse_cash_request', $fileName, 'public');
            }

            return WarehouseCashRequest::create([
                'request_date' => $request->input('request_date'),
                'warehouse_id' => $warehouse->id,
                'warehouse_name' => $warehouse->name,
                'amount' => $request->input('amount'),
                'attachment_path' => $attachmentPath,
                'attachment_name' => $attachmentName,
                'notes' => $request->input('notes'),
            ]);
        });

        return $this->successResponse(
            new WarehouseCashRequestResource($record->load('warehouse')),
            'Warehouse cash request created successfully',
            201
        );
    }

    public function update(UpdateRequest $request, string $uid): JsonResponse
    {
        $record = WarehouseCashRequest::with('warehouse')->where('uid', $uid)->firstOrFail();
        $previousStatus = $record->status;
        $newStatus = $request->input('status');

        DB::transaction(function () use ($record, $request, $previousStatus, $newStatus) {
            $warehouse = Warehouse::where('uid', $request->input('warehouse_uid'))->firstOrFail();

            $updateData = ['status' => $newStatus];

            // Allow editing fields only when the record is still Draft or Revised
            if (in_array($previousStatus, ['Draft', 'Revised'])) {
                $updateData['request_date'] = $request->input('request_date');
                $updateData['warehouse_id'] = $warehouse->id;
                $updateData['warehouse_name'] = $warehouse->name;
                $updateData['amount'] = $request->input('amount');
                $updateData['notes'] = $request->input('notes');
            }

            $record->update($updateData);

            // On first approval: increment warehouse cash balance
            if ($newStatus === 'Approved' && $previousStatus !== 'Approved') {
                Warehouse::where('id', $record->warehouse_id)
                    ->lockForUpdate()
                    ->increment('cash_balance', (float) $record->amount);
            }
        });

        $record->load('warehouse');

        return $this->successResponse(
            new WarehouseCashRequestResource($record),
            'Warehouse cash request updated successfully'
        );
    }

    public function uploadAttachment(Request $request, string $uid): JsonResponse
    {
        $request->validate([
            'attachment' => 'required|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $record = WarehouseCashRequest::where('uid', $uid)->firstOrFail();

        abort_if(
            ! in_array($record->status, ['Draft', 'Revised']),
            422,
            'Attachment can only be updated on Draft or Revised requests.'
        );

        // Delete old file
        if ($record->attachment_path && Storage::disk('public')->exists($record->attachment_path)) {
            Storage::disk('public')->delete($record->attachment_path);
        }

        $file = $request->file('attachment');
        $fileHelper = new FileHelper;
        $fileName = $fileHelper->generateFileName($file);
        $attachmentName = $file->getClientOriginalName();
        $attachmentPath = $file->storeAs('warehouse_cash_request', $fileName, 'public');

        $record->update([
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
        ]);

        return $this->successResponse(
            new WarehouseCashRequestResource($record->load('warehouse')),
            'Attachment uploaded successfully'
        );
    }

    public function destroy(string $uid): JsonResponse
    {
        $record = WarehouseCashRequest::where('uid', $uid)->firstOrFail();

        abort_if(
            ! in_array($record->status, ['Draft', 'Revised']),
            422,
            'Only Draft or Revised requests can be deleted.'
        );

        if ($record->attachment_path && Storage::disk('public')->exists($record->attachment_path)) {
            Storage::disk('public')->delete($record->attachment_path);
        }

        $record->delete();

        return $this->successResponse(null, 'Warehouse cash request deleted successfully');
    }
}
