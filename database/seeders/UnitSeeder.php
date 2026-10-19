<?php

namespace Database\Seeders;

use App\Models\Camera;
use App\Models\Dvr;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $kebunNames = [
            'Sei Galuh', 'Sei Garo', 'Sei Pagar', 'Sei Tapung', 'Sei Rokan',
            'Sei Intan', 'Sei Buatan', 'Sei Kencana', 'Sei Berlian', 'Sei Lindai',
            'Sei Batu Langkah', 'Tandun', 'Terantam', 'Lubuk Dalam', 'Tamora',
            'Tanah Putih', 'Tanjung Medan', 'Air Molek', 'Sei Siantan', 'Bagan Sinembah',
        ];

        $pksNames = [
            'Sei Galuh', 'Sei Tapung', 'Sei Pagar', 'Sei Garo', 'Sei Buatan',
            'Sei Intan', 'Tandun', 'Terantam', 'Lubuk Dalam', 'Tanjung Medan',
            'Tanah Putih', 'Sei Rokan',
        ];

        $biogasNames = [
            'Tandun', 'Terantam', 'Sei Pagar', 'Lubuk Dalam', 'Sei Tapung', 'Sei Rokan',
        ];

        // 1. Buat 20 Unit Kebun
        foreach ($kebunNames as $index => $name) {
            $num = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            $unit = Unit::updateOrCreate(
                ['kode' => "KBN-{$num}"],
                ['nama' => "Kebun {$name}", 'kategori' => 'kebun']
            );

            $this->seedDvrAndCameras($unit, $index, [
                ['ch' => 1, 'lokasi' => 'Pos Satpam Utama', 'kat' => 'Keamanan'],
                ['ch' => 2, 'lokasi' => 'Gerbang Masuk Kebun', 'kat' => 'Gerbang'],
                ['ch' => 3, 'lokasi' => 'Timbangan TBS', 'kat' => 'Timbangan'],
                ['ch' => 4, 'lokasi' => 'Gudang Pupuk & Logistik', 'kat' => 'Gudang'],
            ]);
        }

        // 2. Buat 12 Unit PKS
        foreach ($pksNames as $index => $name) {
            $num = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            $unit = Unit::updateOrCreate(
                ['kode' => "PKS-{$num}"],
                ['nama' => "PKS {$name}", 'kategori' => 'pks']
            );

            $this->seedDvrAndCameras($unit, 20 + $index, [
                ['ch' => 1, 'lokasi' => 'Loading Ramp TBS', 'kat' => 'Loading'],
                ['ch' => 2, 'lokasi' => 'Stasiun Rebusan (Sterilizer)', 'kat' => 'Produksi'],
                ['ch' => 3, 'lokasi' => 'Stasiun Klarifikasi', 'kat' => 'Produksi'],
                ['ch' => 4, 'lokasi' => 'Engine Room & Turbin', 'kat' => 'Power'],
            ]);
        }

        // 3. Buat 6 Unit Biogas
        foreach ($biogasNames as $index => $name) {
            $num = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            $unit = Unit::updateOrCreate(
                ['kode' => "BGS-{$num}"],
                ['nama' => "Biogas Plant {$name}", 'kategori' => 'biogas']
            );

            $this->seedDvrAndCameras($unit, 32 + $index, [
                ['ch' => 1, 'lokasi' => 'Reaktor Anaerobik', 'kat' => 'Reaktor'],
                ['ch' => 2, 'lokasi' => 'Gas Flare Stasiun', 'kat' => 'Flare'],
                ['ch' => 3, 'lokasi' => 'Gas Holder & Scrubber', 'kat' => 'Storage'],
                ['ch' => 4, 'lokasi' => 'Control Room Biogas', 'kat' => 'Control'],
            ]);
        }
    }

    /**
     * @param array<int, array{ch: int, lokasi: string, kat: string}> $cameraSpecs
     */
    private function seedDvrAndCameras(Unit $unit, int $offset, array $cameraSpecs): void
    {
        $dvrName = "DVR {$unit->nama}";
        $hasPublic = ($offset % 2 === 0);

        $dvr = Dvr::firstOrCreate(
            ['unit_id' => $unit->id],
            [
                'nama' => $dvrName,
                'ip_local' => '192.168.1.' . (10 + ($offset % 200)),
                'port_local' => 554,
                'ip_public' => $hasPublic ? '203.0.113.' . (10 + ($offset % 200)) : null,
                'port_public' => $hasPublic ? (38000 + $offset) : null,
                'username' => 'admin',
                'password' => 'password123',
            ]
        );

        $dvr->update(['nama' => $dvrName]);

        foreach ($cameraSpecs as $spec) {
            Camera::firstOrCreate(
                ['dvr_id' => $dvr->id, 'channel' => $spec['ch']],
                [
                    'nama_lokasi' => $spec['lokasi'],
                    'kategori' => $spec['kat'],
                    'is_online' => true,
                    'last_checked_at' => now(),
                ]
            );
        }
    }
}
