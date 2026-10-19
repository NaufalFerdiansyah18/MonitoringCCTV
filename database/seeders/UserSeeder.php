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
        $password = Hash::make('password');

        // Pastikan Grup Teknis utama per kategori unit tersedia
        $grupPks = TechnicalGroup::firstOrCreate(['nama' => 'PKS']);
        TechnicalGroupUnitCategory::firstOrCreate([
            'technical_group_id' => $grupPks->id,
            'kategori' => 'pks',
        ]);

        $grupBiogas = TechnicalGroup::firstOrCreate(['nama' => 'Biogas']);
        TechnicalGroupUnitCategory::firstOrCreate([
            'technical_group_id' => $grupBiogas->id,
            'kategori' => 'biogas',
        ]);

        $grupKebun = TechnicalGroup::firstOrCreate(['nama' => 'Kebun']);
        TechnicalGroupUnitCategory::firstOrCreate([
            'technical_group_id' => $grupKebun->id,
            'kategori' => 'kebun',
        ]);

        // 1. Superadmin (Akses Penuh ke Semua Unit dan Semua Menu Master Data)
        User::updateOrCreate(
            ['email' => 'superadmin@cctv.local'],
            [
                'name' => 'IT Superadmin',
                'password' => $password,
                'role' => User::ROLE_SUPERADMIN,
                'technical_group_id' => null,
            ]
        );

        // 2. Akun Unit PKS (1 akun untuk mengakses SEMUA unit PKS)
        User::updateOrCreate(
            ['email' => 'pks@cctv.local'],
            [
                'name' => 'Unit PKS',
                'password' => $password,
                'role' => User::ROLE_TEKNIS,
                'technical_group_id' => $grupPks->id,
            ]
        );

        // 3. Akun Unit Biogas (1 akun untuk mengakses SEMUA unit Biogas)
        User::updateOrCreate(
            ['email' => 'biogas@cctv.local'],
            [
                'name' => 'Unit Biogas',
                'password' => $password,
                'role' => User::ROLE_TEKNIS,
                'technical_group_id' => $grupBiogas->id,
            ]
        );

        // 4. Akun Unit Kebun (1 akun untuk mengakses SEMUA unit Kebun)
        User::updateOrCreate(
            ['email' => 'kebun@cctv.local'],
            [
                'name' => 'Unit Kebun',
                'password' => $password,
                'role' => User::ROLE_TEKNIS,
                'technical_group_id' => $grupKebun->id,
            ]
        );

        // Bersihkan akun-akun per unit yang berlebih jika ada
        User::query()
            ->where(function ($query) {
                $query->where('email', 'like', 'user.kebun%')
                    ->orWhere('email', 'like', 'user.pks%')
                    ->orWhere('email', 'like', 'user.biogas%')
                    ->orWhereIn('email', ['tanaman@cctv.local', 'tekpol@cctv.local', 'pmo@cctv.local', 'it@cctv.local']);
            })
            ->delete();
    }
}
