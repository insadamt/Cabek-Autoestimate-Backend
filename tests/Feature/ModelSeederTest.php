<?php

namespace Tests\Feature;

use App\Models\Marque;
use App\Models\VehicleModel;
use Database\Seeders\MarqueSeeder;
use Database\Seeders\ModelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_canonical_vehicle_models(): void
    {
        $this->seed(MarqueSeeder::class);
        $this->seed(ModelSeeder::class);

        $this->assertSame(291, VehicleModel::query()->count());

        $renaultId = Marque::query()
            ->where('libelle', 'Renault')
            ->value('id');

        $this->assertNotNull($renaultId);
        $this->assertDatabaseHas('models', [
            'marque_id' => $renaultId,
            'libelle' => 'Clio',
        ]);
    }
}
