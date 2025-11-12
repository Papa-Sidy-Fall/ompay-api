<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MarchandsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $marchands = [
            [
                'nom' => 'Orange Money',
                'code_marchand' => 'ORANGE123',
                'description' => 'Service de paiement mobile Orange',
                'statut' => 'actif'
            ],
            [
                'nom' => 'Wave',
                'code_marchand' => 'WAVE456',
                'description' => 'Service de paiement Wave',
                'statut' => 'actif'
            ],
            [
                'nom' => 'Free Money',
                'code_marchand' => 'FREE789',
                'description' => 'Service de paiement Free',
                'statut' => 'actif'
            ],
            [
                'nom' => 'E-Money',
                'code_marchand' => 'EMONEY001',
                'description' => 'Service de paiement E-Money',
                'statut' => 'actif'
            ],
            [
                'nom' => 'Yoomee',
                'code_marchand' => 'YOOMEE002',
                'description' => 'Service internet et paiement Yoomee',
                'statut' => 'actif'
            ]
        ];

        foreach ($marchands as $marchand) {
            DB::table('marchands')->insert($marchand);
        }
    }
}
