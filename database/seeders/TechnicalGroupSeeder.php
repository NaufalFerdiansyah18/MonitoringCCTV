<?php

namespace Database\Seeders;

use App\Models\TechnicalGroup;
use App\Models\TechnicalGroupUnitCategory;
use Illuminate\Database\Seeder;

class TechnicalGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            'tanaman' => ['kebun'],
            'tekpol' => ['pks'],
        ];

        foreach ($groups as $nama => $kategoris) {
            $group = TechnicalGroup::firstOrCreate(['nama' => $nama]);

            foreach ($kategoris as $kategori) {
                TechnicalGroupUnitCategory::firstOrCreate([
                    'technical_group_id' => $group->id,
                    'kategori' => $kategori,
                ]);
            }
        }
    }
}
