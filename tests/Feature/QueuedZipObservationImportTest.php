<?php

namespace Tests\Feature;

use App\Jobs\PrepareObservationImport;
use App\Jobs\ProcessObservationImportFile;
use App\Models\ObservationImport;
use App\Models\ObservationImportFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QueuedZipObservationImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_zip_upload_creates_a_queued_import(): void
    {
        Queue::fake();
        Storage::fake('local');

        $response = $this->post(route('internal.ingest.store'), [
            'json_file' => UploadedFile::fake()->create('batches.zip', 1024, 'application/zip'),
        ]);

        $observationImport = ObservationImport::query()->firstOrFail();

        $response->assertRedirect(route('internal.imports.show', $observationImport));
        $this->assertTrue($observationImport->auto_import);
        $this->assertSame('queued', $observationImport->status);
        Storage::disk('local')->assertExists($observationImport->archive_path);

        Queue::assertPushed(
            PrepareObservationImport::class,
            fn (PrepareObservationImport $job): bool => $job->observationImportId === $observationImport->id,
        );
    }

    public function test_validate_only_zip_does_not_enable_automatic_import(): void
    {
        Queue::fake();
        Storage::fake('local');

        $this->post(route('internal.ingest.validate'), [
            'json_file' => UploadedFile::fake()->create('batches.zip', 1024, 'application/zip'),
        ]);

        $observationImport = ObservationImport::query()->firstOrFail();

        $this->assertFalse($observationImport->auto_import);
        Queue::assertPushed(PrepareObservationImport::class);
    }

    public function test_ready_import_queues_the_first_json_file(): void
    {
        Queue::fake();

        $observationImport = ObservationImport::factory()->create([
            'status' => 'ready',
            'auto_import' => false,
            'total_files' => 2,
            'validated_files' => 2,
            'total_observations' => 900,
        ]);

        $firstFile = ObservationImportFile::factory()->create([
            'observation_import_id' => $observationImport->id,
            'position' => 1,
            'status' => 'valid',
            'observation_count' => 500,
        ]);

        ObservationImportFile::factory()->create([
            'observation_import_id' => $observationImport->id,
            'position' => 2,
            'status' => 'valid',
            'observation_count' => 400,
        ]);

        $response = $this->post(route('internal.imports.start', $observationImport));

        $response->assertRedirect(route('internal.imports.show', $observationImport));
        $this->assertDatabaseHas('observation_imports', [
            'id' => $observationImport->id,
            'status' => 'importing',
        ]);
        $this->assertDatabaseCount('observation_import_files', 2);
        $this->assertSame(2, $observationImport->files()->where('status', 'queued')->count());

        Queue::assertPushed(
            ProcessObservationImportFile::class,
            fn (ProcessObservationImportFile $job): bool => $job->observationImportFileId === $firstFile->id,
        );
    }

    public function test_status_endpoint_returns_progress_information(): void
    {
        $observationImport = ObservationImport::factory()->create([
            'status' => 'importing',
            'total_files' => 4,
            'validated_files' => 4,
            'processed_files' => 2,
            'total_observations' => 2000,
            'processed_observations' => 1000,
            'inserted_observations' => 920,
            'duplicate_observations' => 80,
        ]);

        ObservationImportFile::factory()->create([
            'observation_import_id' => $observationImport->id,
            'position' => 3,
            'status' => 'importing',
            'filename' => 'batch_0003.json',
        ]);

        $this->getJson(route('internal.imports.status', $observationImport))
            ->assertOk()
            ->assertJsonPath('status', 'importing')
            ->assertJsonPath('progress', 50)
            ->assertJsonPath('processed_files', 2)
            ->assertJsonPath('inserted', 920)
            ->assertJsonPath('current_file', 'batch_0003.json');
    }
}
