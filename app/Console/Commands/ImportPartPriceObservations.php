<?php

namespace App\Console\Commands;

use App\Services\ObservationImportInputReader;
use App\Services\PartPriceObservationIngestor;
use App\Services\PartPriceObservationPayloadValidator;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ImportPartPriceObservations extends Command
{
    protected $signature = 'observations:import
        {path : JSON file, ZIP archive, or directory containing normalized import batches}
        {--dry-run : Validate every batch and print the summary without writing to the database}
        {--yes : Skip the confirmation prompt}
        {--report= : Write a machine-readable JSON report to this path}';

    protected $description = 'Validate and import normalized car-part price observations from JSON, ZIP, or a directory';

    public function handle(
        ObservationImportInputReader $reader,
        PartPriceObservationPayloadValidator $validator,
        PartPriceObservationIngestor $ingestor,
    ): int {
        $startedAt = microtime(true);
        $inputPath = (string) $this->argument('path');

        try {
            $files = $reader->discover($inputPath);
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Cabek AutoEstimate observation import');
        $this->line('Input: '.$inputPath);
        $this->line('Files discovered: '.count($files));
        $this->newLine();
        $this->components->twoColumnDetail('Phase 1', 'Preflight validation');

        $validatedCounts = [];
        $validationErrors = [];
        $totalObservations = 0;
        $validationProgress = $this->output->createProgressBar(count($files));
        $validationProgress->start();

        foreach ($files as $index => $file) {
            try {
                $observations = $validator->validateRawJson($reader->read($file), $file['name']);
                $count = count($observations);
                $validatedCounts[$index] = $count;
                $totalObservations += $count;
            } catch (ValidationException $exception) {
                $validationErrors[] = [
                    'file' => $file['name'],
                    'errors' => $exception->errors(),
                ];
            } catch (Throwable $exception) {
                $validationErrors[] = [
                    'file' => $file['name'],
                    'errors' => ['input' => [$exception->getMessage()]],
                ];
            }

            $validationProgress->advance();
        }

        $validationProgress->finish();
        $this->newLine(2);

        if ($validationErrors !== []) {
            $this->components->error('Preflight failed. Nothing was imported.');
            $this->renderValidationErrors($validationErrors);
            $this->writeReportIfRequested([
                'status' => 'validation_failed',
                'input' => $inputPath,
                'files_discovered' => count($files),
                'observations_validated' => $totalObservations,
                'validation_errors' => $validationErrors,
                'duration_seconds' => round(microtime(true) - $startedAt, 3),
            ]);

            return self::FAILURE;
        }

        $this->components->twoColumnDetail('Valid files', (string) count($files));
        $this->components->twoColumnDetail('Valid observations', number_format($totalObservations));

        if ((bool) $this->option('dry-run')) {
            $this->newLine();
            $this->components->success('Dry run complete. The database was not modified.');
            $this->writeReportIfRequested([
                'status' => 'validated',
                'dry_run' => true,
                'input' => $inputPath,
                'files' => count($files),
                'observations' => $totalObservations,
                'inserted' => 0,
                'skipped_duplicates' => 0,
                'duration_seconds' => round(microtime(true) - $startedAt, 3),
            ]);

            return self::SUCCESS;
        }

        if (! (bool) $this->option('yes') && ! $this->confirm(
            'Import '.number_format($totalObservations).' validated observations into the current database?',
            false,
        )) {
            $this->components->warn('Import cancelled.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->components->twoColumnDetail('Phase 2', 'Database import');

        $inserted = 0;
        $duplicates = 0;
        $processed = 0;
        $fileResults = [];
        $importProgress = $this->output->createProgressBar($totalObservations);
        $importProgress->start();

        try {
            foreach ($files as $index => $file) {
                $observations = $validator->validateRawJson($reader->read($file), $file['name']);
                $result = $ingestor->ingest($observations);

                $inserted += (int) $result['inserted'];
                $duplicates += (int) $result['skipped_duplicates'];
                $processed += (int) $result['received'];

                $fileResults[] = [
                    'file' => $file['name'],
                    'observations' => $validatedCounts[$index] ?? count($observations),
                    'inserted' => (int) $result['inserted'],
                    'skipped_duplicates' => (int) $result['skipped_duplicates'],
                ];

                $importProgress->advance((int) $result['received']);
            }
        } catch (Throwable $exception) {
            $importProgress->finish();
            $this->newLine(2);
            $this->components->error('Import stopped: '.$exception->getMessage());
            $this->components->warn('The command is duplicate-safe. Fix the problem and rerun the same input.');
            $this->writeReportIfRequested([
                'status' => 'import_failed',
                'input' => $inputPath,
                'files' => count($files),
                'observations' => $totalObservations,
                'processed' => $processed,
                'inserted' => $inserted,
                'skipped_duplicates' => $duplicates,
                'error' => $exception->getMessage(),
                'files_processed' => $fileResults,
                'duration_seconds' => round(microtime(true) - $startedAt, 3),
            ]);

            return self::FAILURE;
        }

        $importProgress->finish();
        $this->newLine(2);

        $summary = [
            'status' => 'completed',
            'dry_run' => false,
            'input' => $inputPath,
            'files' => count($files),
            'observations' => $totalObservations,
            'processed' => $processed,
            'inserted' => $inserted,
            'skipped_duplicates' => $duplicates,
            'files_processed' => $fileResults,
            'duration_seconds' => round(microtime(true) - $startedAt, 3),
        ];

        $this->components->success('Import completed.');
        $this->table(
            ['Metric', 'Count'],
            [
                ['JSON files', number_format(count($files))],
                ['Observations', number_format($totalObservations)],
                ['Inserted', number_format($inserted)],
                ['Duplicates skipped', number_format($duplicates)],
            ],
        );

        $this->writeReportIfRequested($summary);

        return self::SUCCESS;
    }

    private function renderValidationErrors(array $validationErrors): void
    {
        foreach ($validationErrors as $failure) {
            $this->newLine();
            $this->line('<fg=red;options=bold>'.$failure['file'].'</>');

            foreach ($failure['errors'] as $field => $messages) {
                foreach ((array) $messages as $message) {
                    $this->line('  - '.$field.': '.$message);
                }
            }
        }
    }

    private function writeReportIfRequested(array $report): void
    {
        $reportPath = $this->option('report');

        if (! is_string($reportPath) || trim($reportPath) === '') {
            return;
        }

        $directory = dirname($reportPath);

        if ($directory !== '.' && ! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Could not create report directory: '.$directory);
        }

        $encoded = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($encoded === false || file_put_contents($reportPath, $encoded."\n") === false) {
            throw new RuntimeException('Could not write import report: '.$reportPath);
        }

        $this->line('Report: '.$reportPath);
    }
}
