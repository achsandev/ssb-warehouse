<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Representasi Item Request untuk konsumen API publik.
 *
 * Berbeda dengan resource internal:
 *   - Tidak membawa internal IDs atau data approver internal.
 *   - Timestamp dalam ISO 8601 UTC (cross-timezone safe).
 *   - Relation minimal — hanya field yang relevan untuk partner.
 */
#[OA\Schema(
    schema: 'ItemRequest',
    type: 'object',
    required: ['uid', 'request_number', 'status'],
    properties: [
        new OA\Property(property: 'uid', type: 'string', format: 'uuid'),
        new OA\Property(property: 'request_number', type: 'string', example: 'PB.04.2026.000123'),
        new OA\Property(property: 'requirement', type: 'string', enum: ['Direct Use', 'Replenishment']),
        new OA\Property(property: 'request_date', type: 'string', format: 'date', example: '2026-04-19'),
        new OA\Property(property: 'unit_code', type: 'string', nullable: true, example: 'UNIT-01'),
        new OA\Property(property: 'wo_number', type: 'string', nullable: true, example: 'WO-2026-045'),
        new OA\Property(property: 'warehouse', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'uid', type: 'string', format: 'uuid'),
            new OA\Property(property: 'name', type: 'string'),
        ]),
        new OA\Property(property: 'is_project', type: 'boolean', example: true),
        new OA\Property(property: 'project_name', type: 'string', nullable: true, example: 'Maintenance Crusher A'),
        new OA\Property(property: 'department_name', type: 'string', example: 'Maintenance'),
        new OA\Property(property: 'status', type: 'string', example: 'Waiting Approval'),
        new OA\Property(property: 'details', type: 'array', items: new OA\Items(ref: '#/components/schemas/ItemRequestDetail')),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'created_by_name', type: 'string', nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'ItemRequestDetail',
    type: 'object',
    properties: [
        new OA\Property(property: 'uid', type: 'string', format: 'uuid'),
        new OA\Property(property: 'item', type: 'object', properties: [
            new OA\Property(property: 'uid', type: 'string', format: 'uuid'),
            new OA\Property(property: 'code', type: 'string'),
            new OA\Property(property: 'name', type: 'string'),
        ]),
        new OA\Property(property: 'unit', type: 'object', properties: [
            new OA\Property(property: 'uid', type: 'string', format: 'uuid'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'symbol', type: 'string'),
        ]),
        new OA\Property(property: 'qty', type: 'integer', example: 5),
        new OA\Property(property: 'description', type: 'string', nullable: true),
    ],
)]
class ItemRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uid'             => (string) $this->uid,
            'request_number'  => (string) $this->request_number,
            'requirement'     => $this->requirement,
            'request_date'    => optional($this->request_date)->toDateString(),
            'unit_code'       => $this->unit_code,
            'wo_number'       => $this->wo_number,

            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->warehouse ? [
                'uid'  => (string) $this->warehouse->uid,
                'name' => (string) $this->warehouse->name,
            ] : null),

            'is_project'      => (bool) $this->is_project,
            'project_name'    => $this->project_name,
            'department_name' => (string) $this->department_name,
            'status'          => (string) $this->status,

            'details' => $this->whenLoaded('item_request_detail', fn () =>
                $this->item_request_detail->map(fn ($row): array => [
                    'uid' => (string) $row->uid,
                    'item' => $row->item ? [
                        'uid'  => (string) $row->item->uid,
                        'code' => (string) $row->item->code,
                        'name' => (string) $row->item->name,
                    ] : null,
                    'unit' => $row->unit ? [
                        'uid'    => (string) $row->unit->uid,
                        'name'   => (string) $row->unit->name,
                        'symbol' => (string) $row->unit->symbol,
                    ] : null,
                    'qty'         => (int) $row->qty,
                    'description' => $row->description,
                ])
            ),

            'created_at'      => optional($this->created_at)->toIso8601String(),
            'created_by_name' => $this->created_by_name,
        ];
    }
}
