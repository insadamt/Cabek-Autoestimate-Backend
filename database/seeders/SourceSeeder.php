<?php

namespace Database\Seeders;

use App\Models\Source;
use Illuminate\Database\Seeder;

class SourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            [
                'libelle' => 'Legacy DB',
                'url' => null,
            ],
            [
                'libelle' => 'Pieces Auto Maroc',
                'url' => 'https://pieces-auto-maroc.com/',
            ],
            [
                'libelle' => 'AtlasAuto',
                'url' => 'https://atlasauto.ma/',
            ],
            [
                'libelle' => 'CarClic',
                'url' => 'https://www.carclic.ma/',
            ],
            [
                'libelle' => 'RT Maroc',
                'url' => 'https://rt-maroc.com/',
            ],
        ];

        foreach ($sources as $source) {
            Source::updateOrCreate(
                ['libelle' => $source['libelle']],
                ['url' => $source['url']]
            );
        }
    }
}
