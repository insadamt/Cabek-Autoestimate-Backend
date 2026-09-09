<?php

namespace Tests\Feature;

use Database\Seeders\MarqueSeeder;
use Database\Seeders\ModelSeeder;
use Database\Seeders\PartSeeder;
use Database\Seeders\SourceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ObservationImportCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $importDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            MarqueSeeder::class,
            ModelSeeder::class,
            PartSeeder::class,
            SourceSeeder::class,
        ]);

        $this->importDirectory = storage_path('framework/testing/observation-import-'.uniqid());
        File::ensureDirectoryExists($this->importDirectory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->importDirectory);

        parent::tearDown();
    }

    public function test_dry_run_validates_without_inserting(): void
    {
        $path = $this->writeBatch('batch.json', [$this->observation()]);

        $this->artisan('observations:import', [
            'path' => $path,
            '--dry-run' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseCount('part_price_observations', 0);
    }

    public function test_import_is_duplicate_safe_when_rerun(): void
    {
        $path = $this->writeBatch('batch.json', [$this->observation()]);

        $this->artisan('observations:import', [
            'path' => $path,
            '--yes' => true,
        ])->assertExitCode(0);

        $this->artisan('observations:import', [
            'path' => $path,
            '--yes' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseCount('part_price_observations', 1);
    }

    public function test_preflight_blocks_all_imports_when_one_batch_is_invalid(): void
    {
        $this->writeBatch('001-valid.json', [$this->observation()]);
        $this->writeBatch('002-invalid.json', [
            $this->observation(['part' => 'Not A Canonical Part']),
        ]);

        $this->artisan('observations:import', [
            'path' => $this->importDirectory,
            '--yes' => true,
        ])->assertExitCode(1);

        $this->assertDatabaseCount('part_price_observations', 0);
    }

    public function test_normalization_review_file_is_ignored(): void
    {
        $this->writeBatch('normalized_import_batch_001.json', [$this->observation()]);
        File::put(
            $this->importDirectory.'/normalization_review.json',
            json_encode(['summary' => ['pending_count' => 3]], JSON_THROW_ON_ERROR),
        );

        $this->artisan('observations:import', [
            'path' => $this->importDirectory,
            '--yes' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseCount('part_price_observations', 1);
    }

    public function test_http_import_surfaces_are_not_exposed(): void
    {
        $this->get('/internal/ingest')->assertNotFound();
        $this->postJson('/api/ingest', [])->assertNotFound();
    }

    private function writeBatch(string $filename, array $observations): string
    {
        $path = $this->importDirectory.'/'.$filename;

        File::put(
            $path,
            json_encode(['observations' => $observations], JSON_THROW_ON_ERROR),
        );

        return $path;
    }

    private function observation(array $overrides = []): array
    {
        return array_replace([
            'marque' => 'Renault',
            'model' => 'Clio',
            'part' => 'Pare-choc Avant',
            'production_from' => 2012,
            'production_to' => 2016,
            'type' => 'adaptable',
            'price' => 490,
            'source' => 'AtlasAuto',
            'source_reference' => null,
            'source_url' => 'https://atlasauto.ma/produit/test-product/',
            'observed_at' => now()->subMinute()->format('Y-m-d H:i:s'),
        ], $overrides);
    }
}
