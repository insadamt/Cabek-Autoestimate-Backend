<?php

namespace Database\Seeders;

use App\Models\Marque;
use Illuminate\Database\Seeder;

class MarqueSeeder extends Seeder
{
    public function run(): void
    {
        $marques = [
            'Alfa Romeo',
            'Audi',
            'Bentley',
            'BMW',
            'BYD',
            'Changan',
            'Chevrolet',
            'Citroën',
            'CUPRA',
            'Dacia',
            'DAF',
            'Daihatsu',
            'Docker',
            'Dodge',
            'DS Automobiles',
            'Fiat',
            'Ford',
            'FUSO',
            'Geely',
            'Honda',
            'Hyundai',
            'Isuzu',
            'Iveco',
            'Jaguar',
            'Jeep',
            'Jetour',
            'Karry',
            'Kia',
            'Land Rover',
            'Lotus',
            'Mahindra',
            'Maserati',
            'Massey Ferguson',
            'Mazda',
            'Mercedes-Benz',
            'MG',
            'MINI',
            'Mitsubishi',
            'Nissan',
            'Opel',
            'Peugeot',
            'Porsche',
            'Renault',
            'Renault Trucks',
            'Scania',
            'SEAT',
            'SsangYong',
            'Suzuki',
            'SYM',
            'Toyota',
            'Volkswagen',
            'Volvo',
            'Zontes',
            'Škoda',
        ];

        foreach ($marques as $libelle) {
            Marque::firstOrCreate(['libelle' => $libelle]);
        }
    }
}
