<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Items\ListItemsRequest;
use App\Http\Resources\Api\ItemResource;
use App\Models\Items;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class ItemController extends Controller
{
    use ApiResponse;

    /**
     * Kolom yang di-select dari `wh_items`. Whitelist eksplisit lebih aman
     * daripada `select('*')` — kolom sensitif / internal (mis. `created_by_id`,
     * `supplier_id`) tidak ikut ter-hydrate ke model.
     */
    private const BASE_COLUMNS = [
        'id', // dibutuhkan untuk FK relasi — disembunyikan oleh $hidden model.
        'uid',
        'code',
        'name',
        'brand_id',
        'item_category_id',
        'unit_id',
        'min_qty',
        'part_number',
        'interchange_part',
        'image',
        'price',
        'created_at',
        'updated_at',
    ];

    #[OA\Get(
        path: '/api/items',
        operationId: 'listItems',
        summary: 'Daftar item (pagination)',
        description: 'Mengembalikan daftar master item dengan dukungan search, '
            .'sort, dan pagination. Memerlukan Bearer token serta permission `items.read`.',
        security: [['bearerAuth' => []]],
        tags: ['Items'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, example: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, example: 10)),
            new OA\Parameter(name: 'sort_by', in: 'query', required: false,
                schema: new OA\Schema(type: 'string', enum: ['code', 'name', 'brand_name', 'item_category_name', 'unit_name', 'min_qty', 'price', 'created_at', 'updated_at'], example: 'created_at'),
            ),
            new OA\Parameter(name: 'sort_dir', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], example: 'desc')),
            new OA\Parameter(name: 'search', in: 'query', required: false, description: 'Cari di code, name, atau part_number (max 120 char)', schema: new OA\Schema(type: 'string', maxLength: 120)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Sukses', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'List of items'),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Item')),
                    new OA\Property(property: 'errors', type: 'string', nullable: true, example: null),
                    new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                ],
            )),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 403, description: 'Forbidden — permission items.read tidak dimiliki', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 429, description: 'Too Many Requests', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
    public function index(ListItemsRequest $request): JsonResponse
    {
        $query = $this->baseQuery();

        $search = $request->searchTerm();
        if ($search !== '') {
            // `LIKE "{search}%"` (prefix) lebih selektif & memanfaatkan index
            // pada `code`/`name` dibanding `%term%` pattern. `addcslashes`
            // mencegah wildcard SQL (`%` / `_`) dari user bocor ke query.
            $prefix = addcslashes($search, '%_\\').'%';

            $query->where(function (Builder $q) use ($prefix): void {
                $q->where('code', 'like', $prefix)
                    ->orWhere('name', 'like', $prefix)
                    ->orWhere('part_number', 'like', $prefix);
            });
        }

        $paginator = $query
            ->orderBy($request->sortBy(), $request->sortDir())
            ->paginate(
                perPage: $request->perPage(),
                columns: self::BASE_COLUMNS,
                pageName: 'page',
                page: $request->page(),
            );

        return $this->successResponse(
            ItemResource::collection($paginator),
            'List of items',
        );
    }

    #[OA\Get(
        path: '/api/items/{uid}',
        operationId: 'getItemByUid',
        summary: 'Detail item',
        description: 'Mengembalikan satu item berdasarkan UUID. Menghindari '
            .'enumerasi ID numerik yang bisa dipakai scanning.',
        security: [['bearerAuth' => []]],
        tags: ['Items'],
        parameters: [
            new OA\Parameter(name: 'uid', in: 'path', required: true, description: 'UUID item', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Sukses', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'Item detail'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Item'),
                    new OA\Property(property: 'errors', type: 'string', nullable: true, example: null),
                ],
            )),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 404, description: 'Item tidak ditemukan', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 429, description: 'Too Many Requests', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
    public function show(string $uid): JsonResponse
    {
        $item = $this->baseQuery()
            ->where('uid', $uid)
            ->firstOrFail(self::BASE_COLUMNS);

        return $this->successResponse(
            new ItemResource($item),
            'Item detail',
        );
    }

    /**
     * Query dasar dengan eager-load terbatas — hanya kolom yang benar-benar
     * ditampilkan resource. Mencegah N+1 sekaligus meminimalkan row payload
     * dari DB.
     *
     * `withSum('stock as total_stock', 'qty')` menambahkan kolom virtual
     * `total_stock` = SUM(wh_stocks.qty) per item via subquery — efisien
     * (1 query untuk semua item) dibanding memuat semua row stock.
     */
    private function baseQuery(): Builder
    {
        return Items::query()
            ->withSum('stock as total_stock', 'qty')
            ->with([
                'brand'    => fn ($q) => $q->select('id', 'uid', 'name'),
                'category' => fn ($q) => $q->select('id', 'uid', 'name'),
                'unit'     => fn ($q) => $q->select('id', 'uid', 'name', 'symbol'),
            ]);
    }
}
