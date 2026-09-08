<?php

namespace Database\Seeders;

use App\Models\Marque;
use App\Models\VehicleModel;
use Illuminate\Database\Seeder;
use RuntimeException;

class ModelSeeder extends Seeder
{
    public function run(): void
    {
        $modelsByMarque = [
            'Alfa Romeo' => ['4C', 'Giulia', 'Giulietta', 'Stelvio'],
            'Audi' => ['A1', 'A3 Berline', 'A3 Sportback', 'A4', 'A5', 'A6', 'A7 Sportback', 'A8', 'Q2', 'Q3', 'Q5', 'Q7', 'Q8', 'RS Q3', 'RS3 Sportback', 'RS5', 'S3 Berline', 'S3 Sportback', 'S5', 'SQ5', 'TT Coupé'],
            'Bentley' => ['Bentayga'],
            'BMW' => ['i3', 'M4 Coupé', 'M5', 'R 1300 GS Adventure', 'Série 1', 'Série 1 5 portes', 'Série 2', 'Série 2 Coupé', 'Série 3', 'Série 3 Gran Turismo', 'Série 4', 'Série 4 Coupé', 'Série 4 Gran Coupé', 'Série 5', 'Série 6 Gran Turismo', 'Série 7', 'X1', 'X3', 'X4', 'X5', 'X6'],
            'BYD' => ['Dolphin', 'Seal U'],
            'Changan' => ['Alsvin'],
            'Chevrolet' => ['Captiva', 'Cruze', 'Spark'],
            'Citroën' => ['Berlingo', 'C-Elysée', 'C1', 'C15', 'C2', 'C3', 'C3 Aircross', 'C4', 'C4 Cactus', 'C4 Picasso', 'C5 Aircross', 'DS5', 'Jumper', 'Nemo', 'Xsara'],
            'CUPRA' => ['Born', 'Formentor'],
            'Dacia' => ['Dokker', 'Duster', 'Lodgy', 'Logan', 'Sandero'],
            'DAF' => ['SB 2750 RS 620'],
            'Daihatsu' => ['Sirion'],
            'Docker' => ['C50', 'C90'],
            'Dodge' => ['Caliber'],
            'DS Automobiles' => ['DS 4', 'DS 7 Crossback'],
            'Fiat' => ['124 Spider', '500', '500L', '500X', 'Doblò', 'Fiorino Combi', 'Palio', 'Punto', 'Siena', 'Tipo', 'Uno'],
            'Ford' => ['B-MAX', 'C-MAX', 'EcoSport', 'Edge', 'Fiesta', 'Focus', 'Ka+', 'Kuga', 'Mondeo', 'Transit Custom Kombi'],
            'FUSO' => ['Canter', 'Fighter'],
            'Geely' => ['Coolray', 'EX5'],
            'Honda' => ['Accord', 'Civic', 'CR-V', 'HR-V', 'Jazz'],
            'Hyundai' => ['Accent', 'Creta', 'Elantra', 'H100', 'HD45', 'i10', 'i20', 'i30', 'i30 SW', 'ix35', 'Kona', 'Santa Fe', 'Tucson'],
            'Isuzu' => ['Axiom', 'D-MAX', 'FSR', 'MU-X'],
            'Iveco' => ['Daily'],
            'Jaguar' => ['E-PACE', 'XF'],
            'Jeep' => ['Avenger', 'Cherokee', 'Grand Cherokee', 'Renegade', 'Wrangler'],
            'Jetour' => ['T2'],
            'Karry' => ['Yoki'],
            'Kia' => ['Ceed', 'Picanto', 'Rio', 'Sorento', 'Sportage'],
            'Land Rover' => ['Discovery', 'Discovery Sport', 'Range Rover', 'Range Rover Evoque', 'Range Rover Sport'],
            'Lotus' => ['Elise'],
            'Mahindra' => ['KUV100'],
            'Maserati' => ['Levante', 'Quattroporte'],
            'Massey Ferguson' => ['275'],
            'Mazda' => ['6e', 'CX-3', 'Mazda3'],
            'Mercedes-Benz' => ['190', '240', '300', 'AMG GT 4-Door Coupé', 'CLA', 'Classe A', 'Classe C', 'Classe E', 'Classe GLC', 'Classe GLE', 'Classe GLE Coupé', 'Classe GLS', 'Classe S', 'Sprinter Combi', 'Vito Tourer'],
            'MG' => ['Cyberster', 'MG3', 'ZS'],
            'MINI' => ['3 Portes', 'Cabriolet', 'Countryman'],
            'Mitsubishi' => ['ASX', 'L200', 'Pajero Sport'],
            'Nissan' => ['370Z', 'e-NV200 Evalia', 'Juke', 'Magnite', 'Micra', 'Navara', 'Note', 'Pathfinder', 'Qashqai'],
            'Opel' => ['Adam', 'Antara', 'Astra', 'Corsa', 'Crossland X', 'Grandland X', 'Mokka X'],
            'Peugeot' => ['107', '108', '2008', '205', '206', '207', '208', '3008', '301', '306', '307', '308', '309', '405', '407', '508', 'Boxer Combi', 'e-Partner Tepee', 'Partner', 'Rifter'],
            'Porsche' => ['718', 'Macan'],
            'Renault' => ['19', 'Captur', 'Clio', 'Express', 'Fluence', 'Kadjar', 'Kangoo', 'Kardian', 'Koleos', 'Laguna', 'Master', 'Mégane', 'Scénic', 'Symbol', 'Talisman', 'Trafic Combi'],
            'Renault Trucks' => ['Premium'],
            'Scania' => ['P 380', 'R450'],
            'SEAT' => ['Alhambra', 'Arona', 'Ateca', 'Ibiza', 'León', 'Tarraco'],
            'SsangYong' => ['Korando', 'Tivoli XLV'],
            'Suzuki' => ['Alto', 'Baleno', 'Celerio', 'S-Cross', 'Swift'],
            'SYM' => ['Symphony ST50'],
            'Toyota' => ['Auris', 'Avensis', 'C-HR', 'Corolla', 'Hilux', 'Land Cruiser', 'RAV4', 'Yaris'],
            'Volkswagen' => ['Amarok', 'Arteon', 'Caddy', 'e-Golf', 'Gol', 'Golf', 'Jetta', 'Passat', 'Polo', 'T-Roc', 'Tiguan', 'Touareg', 'Touran'],
            'Volvo' => ['C40 Recharge', 'S60', 'S90', 'V40', 'V60', 'XC40', 'XC60', 'XC90'],
            'Zontes' => ['ZT350'],
            'Škoda' => ['Citigo e iV', 'Fabia', 'Kamiq', 'Karoq', 'Kodiaq', 'Octavia', 'Rapid Spaceback', 'Scala', 'Superb'],
        ];

        foreach ($modelsByMarque as $marqueLibelle => $models) {
            $marqueId = Marque::query()
                ->where('libelle', $marqueLibelle)
                ->value('id');

            if ($marqueId === null) {
                throw new RuntimeException("Cannot seed models: marque [{$marqueLibelle}] does not exist.");
            }

            foreach ($models as $modelLibelle) {
                VehicleModel::firstOrCreate([
                    'marque_id' => $marqueId,
                    'libelle' => $modelLibelle,
                ]);
            }
        }
    }
}
