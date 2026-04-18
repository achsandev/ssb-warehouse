<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseOrder\StoreRequest;
use App\Http\Requests\PurchaseOrder\UpdateRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\ItemRequest;
use App\Models\Items;
use App\Models\ItemUnits;
use App\Models\PoApprovalLog;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\SettingPoApproval;
use App\Models\Supplier;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $page = $request->integer('page', 1);
        $per_page = $request->integer('per_page', 10);
        $sort_by = $request->string('sort_by', 'created_at')->toString();
        $sort_dir = $request->string('sort_dir', 'desc')->toString();
        $search = $request->string('search')->toString();

        $allowed_sort = [
            'po_number',
            'po_date',
            'total_amount',
            'created_at',
        ];

        if (! in_array($sort_by, $allowed_sort)) {
            $sort_by = 'created_at';
        }

        $query = PurchaseOrder::with([
            'item_request:id,uid,request_number,project_name,wo_number',
            'details.item:id,uid,name',
            'details.unit:id,uid,name,symbol',
            'details.supplier:id,uid,name',
        ]);

        if ($search) {
            $query->where('po_number', 'like', "{$search}%")
                ->orWhere('po_date', 'like', "{$search}%")
                ->orWhere('total_amount', 'like', "{$search}%");
        }

        if ($per_page === -1) {
            $data = $query
                ->orderBy($sort_by, $sort_dir)
                ->get([
                    'id',
                    'uid',
                    'item_request_id',
                    'po_number',
                    'po_date',
                    'project_name',
                    'total_amount',
                    'status',
                    'created_at',
                    'updated_at',
                    'created_by_name',
                    'updated_by_name',
                ]);

            return $this->successResponse(
                PurchaseOrderResource::collection($data),
                'List of purchase orders'
            );
        }

        $data = $query
            ->orderBy($sort_by, $sort_dir)
            ->paginate(
                $per_page, [
                    'id',
                    'uid',
                    'item_request_id',
                    'po_number',
                    'po_date',
                    'project_name',
                    'total_amount',
                    'status',
                    'created_at',
                    'updated_at',
                    'created_by_name',
                    'updated_by_name',
                ],
                'page', $page
            );

        return $this->successResponse(
            PurchaseOrderResource::collection($data),
            'List of purchase orders'
        );
    }

    public function show(string $uid)
    {
        $po = PurchaseOrder::with([
            'item_request:id,uid,request_number,project_name,wo_number',
            'details.item:id,uid,name',
            'details.unit:id,uid,name,symbol',
            'details.supplier:id,uid,name',
            'approval_logs',
        ])->where('uid', $uid)->firstOrFail([
            'id',
            'uid',
            'item_request_id',
            'po_number',
            'po_date',
            'project_name',
            'total_amount',
            'status',
            'created_at',
            'updated_at',
            'created_by_name',
            'updated_by_name',
        ]);

        return $this->successResponse(
            new PurchaseOrderResource($po),
            'Purchase order details'
        );
    }

    public function store(StoreRequest $request)
    {
        $po_date = $request->input('po_date');
        $project_name = $request->input('project_name');
        $item_request_id = ItemRequest::where('uid', $request->input('item_request_uid'))->value('id');
        $total_amount = $request->input('total_amount');
        $details = $request->input('details');

        $data = $request->validated();

        $data['po_date'] = $po_date;
        $data['project_name'] = $project_name;
        $data['item_request_id'] = $item_request_id;
        $data['total_amount'] = $total_amount;

        $po = PurchaseOrder::create($data);

        foreach ($details as $detail) {
            $item_data = Items::where('uid', $detail['item_uid'])->firstOrFail();
            $unit_data = ItemUnits::where('uid', $detail['unit_uid'])->firstOrFail();
            $supplier_data = Supplier::where('uid', $detail['supplier_uid'])->firstOrFail();

            PurchaseOrderDetail::create([
                'purchase_order_id' => $po->id,
                'item_id' => $item_data->id,
                'unit_id' => $unit_data->id,
                'supplier_id' => $supplier_data->id,
                'qty' => $detail['qty'],
                'price' => $detail['price'],
                'total' => $detail['total'],
            ]);
        }

        return $this->successResponse(
            new PurchaseOrderResource($po),
            'Purchase order created successfully',
            201
        );
    }

    public function update(string $uid, UpdateRequest $request)
    {
        $po = PurchaseOrder::where('uid', $uid)->firstOrFail();

        $total_amount = $request->input('total_amount');
        $status = $request->input('status');
        $details = $request->input('details');

        $data = $request->validated();

        $data['total_amount'] = $total_amount;
        $data['status'] = $status;

        $po->update($data);

        // Jika status diubah menjadi Revised, hapus log approval lama agar siklus mulai ulang
        if ($status === 'Revised') {
            PoApprovalLog::where('purchase_order_id', $po->id)->delete();
        }

        foreach ($details as $detail) {
            $item_data = Items::where('uid', $detail['item_uid'])->firstOrFail();
            $unit_data = ItemUnits::where('uid', $detail['unit_uid'])->firstOrFail();
            $supplier_data = Supplier::where('uid', $detail['supplier_uid'])->firstOrFail();

            PurchaseOrderDetail::updateOrCreate(
                [
                    'purchase_order_id' => $po->id,
                    'item_id' => $item_data->id,
                    'unit_id' => $unit_data->id,
                    'supplier_id' => $supplier_data->id,
                ],
                [
                    'qty' => $detail['qty'],
                    'price' => $detail['price'],
                    'total' => $detail['total'],
                ]
            );
        }

        return $this->successResponse(
            new PurchaseOrderResource($po),
            'Purchase order updated successfully'
        );
    }

    public function delete(string $uid)
    {
        $po = PurchaseOrder::where('uid', $uid)->firstOrFail();

        $po->delete();

        return $this->successResponse(
            null,
            'Purchase order deleted successfully'
        );
    }

    public function approve(string $uid, Request $request)
    {
        $request->validate([
            'status' => 'required|in:Approved,Rejected',
            'notes' => 'nullable|string|max:500',
        ]);

        $po = PurchaseOrder::where('uid', $uid)->firstOrFail();

        if (! in_array($po->status, ['Waiting Approval', 'Revised'])) {
            return $this->errorResponse(
                'PO tidak dalam status Waiting Approval atau Revised',
                null,
                422
            );
        }

        $action = $request->input('status');
        $notes = $request->input('notes');

        // Dapatkan level approval yang dibutuhkan berdasarkan total_amount PO
        $requiredLevels = SettingPoApproval::getRequiredLevels((float) $po->total_amount);

        if ($requiredLevels->isEmpty()) {
            $po->update(['status' => 'Approved']);

            return $this->successResponse(
                new PurchaseOrderResource($po),
                'PO disetujui (tidak ada level approval yang dibutuhkan)'
            );
        }

        // Level yang sudah diproses (Approved maupun Rejected)
        $processedLevels = PoApprovalLog::where('purchase_order_id', $po->id)
            ->pluck('approval_level')
            ->toArray();

        // Cari level berikutnya yang belum diproses (harus berurutan)
        $nextSetting = $requiredLevels->first(
            fn ($s) => ! in_array($s->level, $processedLevels)
        );

        if (! $nextSetting) {
            return $this->errorResponse(
                'Semua level approval sudah selesai',
                null,
                422
            );
        }

        // Pastikan user memiliki role yang sesuai dengan level berikutnya
        $user = Auth::user();
        $requiredRole = $nextSetting->role;
        $requiredRoleName = $requiredRole?->name;
        if (! $requiredRoleName || ! $user->hasRole($requiredRoleName)) {
            return $this->errorResponse(
                "Hanya {$requiredRoleName} yang dapat menyetujui pada level {$nextSetting->level}",
                null,
                403
            );
        }

        // Catat approval ke log
        PoApprovalLog::create([
            'purchase_order_id' => $po->id,
            'approval_level' => $nextSetting->level,
            'role_name' => $requiredRoleName,
            'status' => $action,
            'notes' => $notes,
            'approved_by_id' => $user->id,
            'approved_by_name' => $user->name,
        ]);

        if ($action === 'Rejected') {
            // Jika ditolak, langsung set status PO menjadi Rejected
            $po->update(['status' => 'Rejected']);
        } else {
            // Cek apakah semua level yang dibutuhkan sudah Approved
            $approvedLevels = PoApprovalLog::where('purchase_order_id', $po->id)
                ->where('status', 'Approved')
                ->pluck('approval_level')
                ->toArray();

            $allApproved = $requiredLevels->every(
                fn ($s) => in_array($s->level, $approvedLevels)
            );

            if ($allApproved) {
                // Semua level sudah Approved → PO Approved
                $po->update(['status' => 'Approved']);
            } else {
                // Jika sebelumnya Revised, kembalikan ke Waiting Approval
                if ($po->status === 'Revised') {
                    $po->update(['status' => 'Waiting Approval']);
                }
            }
        }

        $po->load([
            'item_request:id,uid,request_number,project_name,wo_number',
            'details.item:id,uid,name',
            'details.unit:id,uid,name,symbol',
            'details.supplier:id,uid,name',
            'approval_logs',
        ]);

        $message = $action === 'Rejected'
            ? 'PO ditolak'
            : ($po->status === 'Approved' ? 'PO disetujui' : 'Approval dicatat, menunggu level berikutnya');

        return $this->successResponse(
            new PurchaseOrderResource($po),
            $message
        );
    }
}
