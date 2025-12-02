<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'nom' => 'Ayibe Fall',
            'telephone' => '+221775209522',
            'pin' => '1234',
            'statut' => 'actif',
        ]);

        User::create([
            'nom' => 'Papa Sidy Fall',
            'telephone' => '+221775943708',
            'pin' => '1234',
            'statut' => 'actif',
        ]);
    }
}