<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PoliSeeder::class,
        ]);

        // Create Superadmin
        \App\Models\User::create([
            'name' => 'Super Admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('password'),
            'role' => 'superadmin',
        ]);

        // Create Staff for each Poli
        $polis = \App\Models\Poli::all();
        foreach ($polis as $poli) {
            \App\Models\User::create([
                'name' => 'Staff ' . $poli->name,
                'email' => strtolower(str_replace(' ', '', $poli->name)) . '@staff.com',
                'password' => bcrypt('password'),
                'role' => 'staff',
                'poli_id' => $poli->id,
            ]);
        }
    }
}
