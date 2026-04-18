<?php

namespace Database\Seeders;

use App\Models\SettingPoApproval;
use Illuminate\Database\Seeder;

class SettingPoApprovalSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            [
                'level' => 1,
                'role_name' => 'Kepala Gudang',
                'min_amount' => null,
                'description' => 'Approval pertama, wajib untuk semua PO',
                'is_active' => true,
            ],
            [
                'level' => 2,
                'role_name' => 'Manager Divisi Operasional',
                'min_amount' => null,
                'description' => 'Approval kedua, wajib untuk semua PO',
                'is_active' => true,
            ],
            [
                'level' => 3,
                'role_name' => 'Direktur',
                'min_amount' => 1000000.00,
                'description' => 'Approval ketiga, hanya untuk PO dengan total >= Rp 1.000.000',
                'is_active' => true,
            ],
        ];

        foreach ($levels as $level) {
            SettingPoApproval::firstOrCreate(
                ['level' => $level['level']],
                array_merge($level, [
                    'created_by_id' => 1,
                    'created_by_name' => 'System',
                ])
            );
        }
    }
}
