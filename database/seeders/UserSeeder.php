<?php

namespace Database\Seeders;

use App\Models\TechnicalGroup;
use App\Models\TechnicalGroupUnitCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan grup 'kebun' tersedia
        $grupKebun = TechnicalGroup::firstOrCreate(['nama' => 'kebun']);
        TechnicalGroupUnitCategory::firstOrCreate([
            'technical_group_id' => $grupKebun->id,
            'kategori' => 'kebun',
        ]);

        // Pastikan grup 'ro' tersedia
        $grupRo = TechnicalGroup::firstOrCreate(['nama' => 'ro']);
        TechnicalGroupUnitCategory::firstOrCreate([
            'technical_group_id' => $grupRo->id,
            'kategori' => 'ro',
        ]);

        // Pastikan grup 'biogas' tersedia
        $grupBiogas = TechnicalGroup::firstOrCreate(['nama' => 'biogas']);
        TechnicalGroupUnitCategory::firstOrCreate([
            'technical_group_id' => $grupBiogas->id,
            'kategori' => 'biogas',
        ]);

        // User Kebun (Hanya akses unit berkategori kebun)
        User::updateOrCreate(
            ['email' => 'kebun@cctv.local'],
            [
                'name' => 'User Kebun',
                'password' => Hash::make('password'),
                'role' => User::ROLE_TEKNIS,
                'technical_group_id' => $grupKebun->id,
            ]
        );

        // User RO (Hanya akses unit berkategori RO)
        User::updateOrCreate(
            ['email' => 'ro@cctv.local'],
            [
                'name' => 'User RO',
                'password' => Hash::make('password'),
                'role' => User::ROLE_TEKNIS,
                'technical_group_id' => $grupRo->id,
            ]
        );

        // User Biogas (Hanya akses unit berkategori Biogas)
        User::updateOrCreate(
            ['email' => 'biogas@cctv.local'],
            [
                'name' => 'User Biogas',
                'password' => Hash::make('password'),
                'role' => User::ROLE_TEKNIS,
                'technical_group_id' => $grupBiogas->id,
            ]
        );
    }
}
