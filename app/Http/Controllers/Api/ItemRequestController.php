<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ItemRequests\StoreItemRequestRequest;
use App\Http\Resources\Api\ItemRequestResource;
use App\Models\ItemRequest;
use App\Models\ItemRequestDetail;
use App\Models\Items;
use App\Models\ItemUnits;
use App\Models\Warehouse;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class ItemRequestController extends Controller
{
    use ApiResponse;

    #[OA\Post(
        path: '/api/item-requests',
        operationId: 'storeItemRequest',
        summary: 'Buat Item Request baru',
        description: 'Membuat permintaan barang baru dengan status awal '
            .'`Waiting Approval`. Identifier warehouse, item, dan unit memakai '
            .'UUID, bukan ID numerik — cegah enumerasi. Operasi transactional: '
            .'bila pembuatan header berhasil tapi detail gagal, seluruh data '
            .'di-rollback.',
        security: [['bearerAuth' => []]],
        tags: ['Item Requests'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['requirement', 'request_date', 'warehouse_uid', 'is_project', 'department_name', 'details'],
                properties: [
                    new OA\Property(property: 'requirement', type: 'string', enum: ['Direct Use', 'Replenishment'], example: 'Replenishment'),
                    new OA\Property(property: 'request_date', type: 'string', format: 'date', example: '2026-04-19'),
                    new OA\Property(property: 'unit_code', type: 'string', nullable: true, maxLength: 100, example: 'UNIT-01'),
                    new OA\Property(property: 'wo_number', type: 'string', nullable: true, maxLength: 100, example: 'WO-2026-045'),
                    new OA\Property(property: 'warehouse_uid', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'is_project', type: 'boolean', example: true),
                    new OA\Property(property: 'project_name', type: 'string', nullable: true, maxLength: 255, example: 'Maintenance Crusher A'),
                    new OA\Property(property: 'department_name', type: 'string', maxLength: 255, example: 'Maintenance'),
                    new OA\Property(property: 'details', type: 'array', minItems: 1, maxItems: 100, items: new OA\Items(
                        required: ['item_uid', 'unit_uid', 'qty'],
                        properties: [
                            new OA\Property(property: 'item_uid', type: 'string', format: 'uuid'),
                            new OA\Property(property: 'unit_uid', type: 'string', format: 'uuid'),
                            new OA\Property(property: 'qty', type: 'integer', minimum: 1, maximum: 999999, example: 5),
                            new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 500),
                        ],
                    )),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Item request berhasil dibuat', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'Item request created successfully'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/ItemRequest'),
                    new OA\Property(property: 'errors', type: 'string', nullable: true, example: null),
                ],
            )),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 403, description: 'Forbidden — permission `item_request.create` tidak dimiliki', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 429, description: 'Too Many Requests', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
    public function store(StoreItemRequestRequest $request): JsonResponse
    {
        $data = $request->validated();
        $details = $data['details'];
        unset($data['details']);

        // ── Resolve UUID → ID di luar transaction (read-only) ────────────
        // Dilakukan di luar transaction supaya kalau UUID invalid, error
        // muncul sebelum memulai write transaction. Kedua FK sudah divalidasi
        // `exists` oleh FormRequest — di sini kita ambil PK untuk insert.
        $warehouseId = Warehouse::where('uid', $data['warehouse_uid'])->value('id');
        unset($data['warehouse_uid']);

        // Batch resolve item & unit IDs untuk menghindari N+1 query saat loop.
        $itemUids = array_column($details, 'item_uid');
        $unitUids = array_column($details, 'unit_uid');

        $itemIdMap = Items::whereIn('uid', $itemUids)->pluck('id', 'uid')->all();
        $unitIdMap = ItemUnits::whereIn('uid', $unitUids)->pluck('id', 'uid')->all();

        // ── Normalize header ──────────────────────────────────────────────
        $data['warehouse_id']       = $warehouseId;
        $data['status']             = 'Waiting Approval';
        $data['created_by_role_id'] = $request->user()?->roles()->first()?->id;

        // Non-project → paksa project_name null (defense di BE selain rule
        // `required_if` di FormRequest).
        if (empty($data['is_project'])) {
            $data['project_name'] = null;
        }

        // ── Transactional write ───────────────────────────────────────────
        $itemRequest = DB::transaction(function () use ($data, $details, $itemIdMap, $unitIdMap): ItemRequest {
            $header = ItemRequest::create($data);

            $rows = array_map(fn (array $row): array => [
                'item_request_id' => $header->id,
                'item_id'         => $itemIdMap[$row['item_uid']],
                'unit_id'         => $unitIdMap[$row['unit_uid']],
                'qty'             => (int) $row['qty'],
                'description'     => $row['description'] ?? null,
            ], $details);

            // Bulk insert detail. Observer untuk `ItemRequestDetail` tidak
            // ter-trigger oleh bulk insert — untuk audit trail per-baris,
            // loop create satu-satu. Di sini prioritas atomicity + kinerja
            // lebih penting dari per-row observer.
            foreach ($rows as $row) {
                ItemRequestDetail::create($row);
            }

            return $header;
        });

        // Load relasi untuk resource response (di luar transaction — read-only).
        $itemRequest->load([
            'warehouse',
            'item_request_detail.item:id,uid,code,name',
            'item_request_detail.unit:id,uid,name,symbol',
        ]);

        return $this->successResponse(
            new ItemRequestResource($itemRequest),
            'Item request created successfully',
            201,
        );
    }
}
