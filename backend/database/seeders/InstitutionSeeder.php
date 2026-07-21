<?php

namespace Database\Seeders;

use App\Enums\InstitutionType;
use App\Models\Institution;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    public function run(): void
    {
        $banks = [
            ['GCB Bank', 'GCB'],
            ['Ecobank Ghana', 'ECO'],
            ['Absa Bank Ghana', 'ABSA'],
            ['Stanbic Bank Ghana', 'STAN'],
            ['Fidelity Bank Ghana', 'FID'],
            ['CalBank', 'CAL'],
            ['Zenith Bank Ghana', 'ZEN'],
            ['Standard Chartered Ghana', 'SCB'],
            ['UBA Ghana', 'UBA'],
            ['Prudential Bank', 'PRU'],
        ];

        $mobileMoney = [
            ['MTN Mobile Money', 'MTN'],
            ['Telecel Cash', 'TCL'],
            ['AT Money', 'ATM'],
        ];

        foreach ($banks as [$name, $code]) {
            Institution::firstOrCreate(['code' => $code], [
                'name' => $name,
                'type' => InstitutionType::Bank,
            ]);
        }

        foreach ($mobileMoney as [$name, $code]) {
            Institution::firstOrCreate(['code' => $code], [
                'name' => $name,
                'type' => InstitutionType::MobileMoney,
            ]);
        }
    }
}
