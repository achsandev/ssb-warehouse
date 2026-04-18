<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Http\Requests\CashPayment\StoreRequest;
use App\Http\Requests\CashPayment\UpdateRequest;
use App\Http\Resources\CashPaymentResource;
use App\Models\CashPayment;
use App\Models\Warehouse;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CashPaymentController extends Controller
{
    use ApiResponse;

    public function warehousesLookup(): JsonResponse
    {
        $warehouses = Warehouse::select('id', 'uid', 'name', 'cash_balance')
            ->orderBy('name')
            ->get()
            ->map(fn ($w) => [
                'uid'           => $w->uid,
                'name'          => $w->name,
                'cash_balance'  => (float) $w->cash_balance,
            ]);

        return $this->successResponse($warehouses, 'List of warehouses');
    }

    public function index(Request $request): JsonResponse
    {
        $page    = $request->integer('page', 1);
        $perPage = $request->integer('per_page', 10);
        $sortBy  = $request->string('sort_by', 'created_at')->toString();
        $sortDir = $request->string('sort_dir', 'desc')->toString();
        $search  = $request->string('search')->toString();

        $allowedSort = ['payment_number', 'payment_date', 'warehouse_name', 'amount', 'status', 'created_at'];
        if (! in_array($sortBy, $allowedSort)) {
            $sortBy = 'created_at';
        }

        $columns = [
            'id', 'uid', 'payment_number', 'payment_date',
            'warehouse_id', 'warehouse_name', 'description', 'amount',
            'spk_path', 'spk_name',
            'attachment_path', 'attachment_name',
            'notes', 'status',
            'created_at', 'updated_at', 'created_by_name', 'updated_by_name',
        ];

        $query = CashPayment::with(['warehouse:id,uid,name,cash_balance']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "{$search}%")
                    ->orWhere('warehouse_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($perPage === -1) {
            $data = $query->orderBy($sortBy, $sortDir)->get($columns);

            return $this->successResponse(
                CashPaymentResource::collection($data),
                'List of cash payments'
            );
        }

        $data = $query->orderBy($sortBy, $sortDir)->paginate($perPage, $columns, 'page', $page);

        return $this->successResponse(
            CashPaymentResource::collection($data),
            'List of cash payments'
        );
    }

    public function show(string $uid): JsonResponse
    {
        $record = CashPayment::with(['warehouse:id,uid,name,cash_balance'])
            ->where('uid', $uid)
            ->firstOrFail();

        return $this->successResponse(
            new CashPaymentResource($record),
            'Detail cash payment'
        );
    }

    public function store(StoreRequest $request): JsonResponse
    {
        $warehouse = Warehouse::where('uid', $request->input('warehouse_uid'))->firstOrFail();

        $record = DB::transaction(function () use ($request, $warehouse) {
            $fileHelper = new FileHelper;
            $spkPath = null;
            $spkName = null;
            $attachmentPath = null;
            $attachmentName = null;

            if ($request->hasFile('spk')) {
                $file = $request->file('spk');
                $spkName = $file->getClientOriginalName();
                $spkPath = $file->storeAs('cash_payment/spk', $fileHelper->generateFileName($file), 'public');
            }

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $attachmentName = $file->getClientOriginalName();
                $attachmentPath = $file->storeAs('cash_payment/attachment', $fileHelper->generateFileName($file), 'public');
            }

            return CashPayment::create([
                'payment_date'    => $request->input('payment_date'),
                'warehouse_id'    => $warehouse->id,
                'warehouse_name'  => $warehouse->name,
                'description'     => $request->input('description'),
                'amount'          => $request->input('amount'),
                'spk_path'        => $spkPath,
                'spk_name'        => $spkName,
                'attachment_path' => $attachmentPath,
                'attachment_name' => $attachmentName,
                'notes'           => $request->input('notes'),
            ]);
        });

        return $this->successResponse(
            new CashPaymentResource($record->load('warehouse')),
            'Cash payment created successfully',
            201
        );
    }

    public function update(UpdateRequest $request, string $uid): JsonResponse
    {
        $record         = CashPayment::with('warehouse')->where('uid', $uid)->firstOrFail();
        $previousStatus = $record->status;
        $newStatus      = $request->input('status');

        DB::transaction(function () use ($record, $request, $previousStatus, $newStatus) {
            $warehouse = Warehouse::where('uid', $request->input('warehouse_uid'))->firstOrFail();

            $updateData = ['status' => $newStatus];

            // Allow editing main fields only when still Draft or Revised
            if (in_array($previousStatus, ['Draft', 'Revised'])) {
                $updateData['payment_date']   = $request->input('payment_date');
                $updateData['warehouse_id']   = $warehouse->id;
                $updateData['warehouse_name'] = $warehouse->name;
                $updateData['description']    = $request->input('description');
                $updateData['amount']         = $request->input('amount');
                $updateData['notes']          = $request->input('notes');
            }

            $record->update($updateData);

            // On first approval: deduct warehouse cash balance
            if ($newStatus === 'Approved' && $previousStatus !== 'Approved') {
                Warehouse::where('id', $record->warehouse_id)
                    ->lockForUpdate()
                    ->decrement('cash_balance', (float) $record->amount);
            }
        });

        $record->load('warehouse');

        return $this->successResponse(
            new CashPaymentResource($record),
            'Cash payment updated successfully'
        );
    }

    public function destroy(string $uid): JsonResponse
    {
        $record = CashPayment::where('uid', $uid)->firstOrFail();

        abort_if(
            ! in_array($record->status, ['Draft', 'Revised']),
            422,
            'Only Draft or Revised cash payments can be deleted.'
        );

        // Delete associated files
        foreach (['spk_path', 'attachment_path'] as $field) {
            if ($record->$field && Storage::disk('public')->exists($record->$field)) {
                Storage::disk('public')->delete($record->$field);
            }
        }

        $record->delete();

        return $this->successResponse(null, 'Cash payment deleted successfully');
    }
}
