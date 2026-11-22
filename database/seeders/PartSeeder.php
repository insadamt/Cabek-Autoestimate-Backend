<?php

namespace Database\Seeders;

use App\Models\Part;
use Illuminate\Database\Seeder;

class PartSeeder extends Seeder
{
    public function run(): void
    {
        $parts = [
            'Aile Arrière Droit',
            'Aile Arrière Gauche',
            'Aile Avant Droit',
            'Aile Avant Gauche',
            'Antibrouillard Arrière Droit',
            'Antibrouillard Arrière Gauche',
            'Antibrouillard Avant Droit',
            'Antibrouillard Avant Gauche',
            'Bas De Caisse Droit',
            'Bas De Caisse Gauche',
            'Calandre',
            'Caméra De Recul',
            'Capot',
            'Capteur De Stationnement',
            'Clignotant Avant Droit',
            'Clignotant Avant Gauche',
            'Feu Arrière Droit',
            'Feu Arrière Gauche',
            'Feu De Jour Avant Droit',
            'Feu De Jour Avant Gauche',
            'Grille Pare-choc Avant',
            'Hayon',
            'Logo / Emblème',
            'Lunette Arrière',
            'Malle Arrière',
            'Marche-pied',
            'Optique Avant Droit',
            'Optique Avant Gauche',
            'Pare-brise',
            'Pare-choc Arrière',
            'Pare-choc Avant',
            'Porte Arrière Droit',
            'Porte Arrière Gauche',
            'Porte Avant Droit',
            'Porte Avant Gauche',
            'Porte Battante Arrière Droit',
            'Porte Battante Arrière Gauche',
            'Porte Coulissante Droit',
            'Porte Coulissante Gauche',
            'Rétroviseur Droit',
            'Rétroviseur Gauche',
            'Spoiler Arrière',
            'Spoiler Avant',
            'Toit / Pavillon',
            'Vitre Porte Arrière Droit',
            'Vitre Porte Arrière Gauche',
            'Vitre Porte Avant Droit',
            'Vitre Porte Avant Gauche',
        ];

        foreach ($parts as $libelle) {
            Part::firstOrCreate(['libelle' => $libelle]);
        }
    }
}
