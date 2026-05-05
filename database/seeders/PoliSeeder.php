<?php

namespace Database\Seeders;

use App\Models\Poli;
use Illuminate\Database\Seeder;

class PoliSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $polis = [
            [
                'name' => 'Poli Umum',
                'code' => 'A',
                'description' => 'Layanan kesehatan umum dan pemeriksaan rutin.',
                'icon' => 'fas fa-stethoscope',
            ],
            [
                'name' => 'Poli Gigi',
                'code' => 'B',
                'description' => 'Layanan kesehatan gigi dan mulut.',
                'icon' => 'fas fa-tooth',
            ],
            [
                'name' => 'Poli Anak',
                'code' => 'C',
                'description' => 'Layanan kesehatan spesialis anak.',
                'icon' => 'fas fa-baby',
            ],
            [
                'name' => 'Poli Kandungan',
                'code' => 'D',
                'description' => 'Layanan kesehatan ibu dan kandungan.',
                'icon' => 'fas fa-female',
            ],
            [
                'name' => 'Poli Mata',
                'code' => 'E',
                'description' => 'Layanan kesehatan spesialis mata.',
                'icon' => 'fas fa-eye',
            ],
        ];

        foreach ($polis as $poli) {
            Poli::create($poli);
        }
    }
}
