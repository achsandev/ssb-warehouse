<?php

namespace App\Http\Requests\ItemTransfer;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transfer_date'       => 'required|date',

            'from_warehouse_uid'  => 'required|string|exists:wh_warehouse,uid',
            'from_rack_uid'       => 'nullable|string|exists:wh_rack,uid',
            'from_tank_uid'       => 'nullable|string|exists:wh_tank,uid',

            'to_warehouse_uid'    => 'required|string|exists:wh_warehouse,uid',
            'to_rack_uid'         => 'nullable|string|exists:wh_rack,uid',
            'to_tank_uid'         => 'nullable|string|exists:wh_tank,uid',

            'notes'               => 'nullable|string|max:1000',
            'parent_transfer_uid' => 'nullable|string|exists:wh_item_transfer,uid',

            'details'             => 'required|array|min:1',
            'details.*.item_uid'  => 'required|string|exists:wh_items,uid',
            'details.*.unit_uid'  => 'required|string|exists:wh_item_units,uid',
            'details.*.qty'       => 'required|numeric|min:0.01',
            'details.*.description' => 'nullable|string|max:500',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $fromWh = $this->input('from_warehouse_uid');
            $toWh   = $this->input('to_warehouse_uid');
            $fromRack = $this->input('from_rack_uid');
            $toRack   = $this->input('to_rack_uid');
            $fromTank = $this->input('from_tank_uid');
            $toTank   = $this->input('to_tank_uid');

            // Sumber dan tujuan harus berbeda minimal di salah satu level lokasi
            $sameLocation =
                $fromWh === $toWh &&
                ($fromRack ?? null) === ($toRack ?? null) &&
                ($fromTank ?? null) === ($toTank ?? null);

            if ($sameLocation) {
                $v->errors()->add('to_warehouse_uid', 'Lokasi tujuan harus berbeda dari lokasi sumber.');
            }

            // Rak dan tangki tidak boleh dipilih bersamaan (mutually exclusive)
            if ($fromRack && $fromTank) {
                $v->errors()->add('from_rack_uid', 'Pilih salah satu: rak atau tangki sumber.');
            }
            if ($toRack && $toTank) {
                $v->errors()->add('to_rack_uid', 'Pilih salah satu: rak atau tangki tujuan.');
            }
        });
    }
}
