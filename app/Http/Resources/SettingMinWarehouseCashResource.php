<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Output untuk modul Setting Min Warehouse Cash.
 *
 * Sengaja minimal — hanya field yang relevan untuk pengaturan threshold:
 *   - identitas warehouse (uid + name + address)
 *   - cash_balance saat ini (untuk konteks visual)
 *   - min_cash (nilai threshold yang di-edit admin)
 *   - is_below_min (computed flag — admin langsung tahu mana yang under-budget)
 *
 * `id` dan internal field lainnya disembunyikan oleh `$hidden` di Warehouse model.
 */
class SettingMinWarehouseCashResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $cashBalance = (float) ($this->cash_balance ?? 0);
        $minCash = $this->min_cash !== null ? (float) $this->min_cash : null;

        return [
            'uid'          => (string) $this->uid,
            'name'         => (string) $this->name,
            'address'      => $this->address,
            'cash_balance' => $cashBalance,
            'min_cash'     => $minCash,
            // Status: false bila tidak di-set (null), false bila diatas threshold,
            // true bila di-bawah. Memudahkan UI render chip warning langsung.
            'is_below_min' => $minCash !== null && $cashBalance < $minCash,
            'updated_at'   => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
