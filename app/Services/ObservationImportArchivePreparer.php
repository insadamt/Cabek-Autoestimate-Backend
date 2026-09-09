<?php

namespace App\Services;

use App\Models\ObservationImport;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;
use ZipArchive;

class ObservationImportArchivePreparer
{
    public function prepare(ObservationImport $observationImport): void
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('ZIP imports require the PHP zip extension.');
        }

        $disk = Storage::disk('local');
        $archivePath = $observationImport->archive_path;

        if (! is_string($archivePath) || ! $disk->exists($archivePath)) {
            throw new RuntimeException('The uploaded ZIP archive could not be found.');
        }

        $zip = new ZipArchive;
        $openResult = $zip->open($disk->path($archivePath));

        if ($openResult !== true) {
            throw new RuntimeException('The uploaded file is not a readable ZIP archive.');
        }

        $filesDirectory = 'observation-imports/'.$observationImport->id.'/files';

        try {
            $entries = $this->collectJsonEntries($zip);

            $disk->deleteDirectory($filesDirectory);
            $observationImport->files()->delete();

            $position = 0;
            $actualUncompressedBytes = 0;

            foreach ($entries as $entry) {
                $contents = $zip->getFromIndex($entry['index']);

                if (! is_string($contents)) {
                    throw new RuntimeException('Could not read '.$entry['name'].' from the ZIP archive.');
                }

                $actualBytes = strlen($contents);

                if ($actualBytes > (int) config('observation_imports.max_json_bytes')) {
                    throw new RuntimeException($entry['name'].' exceeds the 5 MB JSON file limit.');
                }

                $actualUncompressedBytes += $actualBytes;

                if ($actualUncompressedBytes > (int) config('observation_imports.max_uncompressed_bytes')) {
                    throw new RuntimeException('The ZIP archive expands beyond the allowed uncompressed size.');
                }

                $position++;
                $storedPath = $filesDirectory.'/'.str_pad((string) $position, 6, '0', STR_PAD_LEFT).'.json';

                if (! $disk->put($storedPath, $contents)) {
                    throw new RuntimeException('Could not store '.$entry['name'].' for queued processing.');
                }

                $observationImport->files()->create([
                    'filename' => $entry['name'],
                    'stored_path' => $storedPath,
                    'position' => $position,
                    'status' => 'pending_validation',
                ]);
            }
        } catch (Throwable $exception) {
            $disk->deleteDirectory($filesDirectory);
            $observationImport->files()->delete();

            throw $exception;
        } finally {
            $zip->close();
        }

        $disk->delete($archivePath);

        $observationImport->update([
            'archive_path' => null,
            'total_files' => $position,
            'validated_files' => 0,
            'processed_files' => 0,
            'total_observations' => 0,
            'processed_observations' => 0,
            'inserted_observations' => 0,
            'duplicate_observations' => 0,
            'failed_files' => 0,
            'error_message' => null,
        ]);
    }

    private function collectJsonEntries(ZipArchive $zip): array
    {
        $entries = [];
        $declaredUncompressedBytes = 0;
        $maxFiles = (int) config('observation_imports.max_json_files');
        $maxJsonBytes = (int) config('observation_imports.max_json_bytes');
        $maxUncompressedBytes = (int) config('observation_imports.max_uncompressed_bytes');

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);

            if (! is_array($stat) || ! isset($stat['name'])) {
                throw new RuntimeException('The ZIP archive contains an unreadable entry.');
            }

            $name = (string) $stat['name'];

            if (! $this->isSafeEntryName($name)) {
                throw new RuntimeException('The ZIP archive contains an unsafe path: '.$name);
            }

            if (str_ends_with(str_replace('\\', '/', $name), '/')) {
                continue;
            }

            if ($this->isIgnoredMetadata($name)) {
                continue;
            }

            if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'json') {
                throw new RuntimeException('ZIP archives may contain only JSON files. Unexpected entry: '.$name);
            }

            $declaredBytes = (int) ($stat['size'] ?? 0);

            if ($declaredBytes > $maxJsonBytes) {
                throw new RuntimeException($name.' exceeds the 5 MB JSON file limit.');
            }

            $declaredUncompressedBytes += $declaredBytes;

            if ($declaredUncompressedBytes > $maxUncompressedBytes) {
                throw new RuntimeException('The ZIP archive expands beyond the allowed uncompressed size.');
            }

            $entries[] = [
                'index' => $index,
                'name' => $name,
            ];

            if (count($entries) > $maxFiles) {
                throw new RuntimeException('The ZIP archive contains more than '.$maxFiles.' JSON files.');
            }
        }

        if ($entries === []) {
            throw new RuntimeException('The ZIP archive does not contain any JSON files.');
        }

        return $entries;
    }

    private function isSafeEntryName(string $name): bool
    {
        $normalized = str_replace('\\', '/', $name);

        if ($normalized === '' || str_contains($normalized, "\0") || str_starts_with($normalized, '/')) {
            return false;
        }

        if (preg_match('/^[A-Za-z]:\//', $normalized) === 1) {
            return false;
        }

        foreach (explode('/', $normalized) as $segment) {
            if ($segment === '..') {
                return false;
            }
        }

        return true;
    }

    private function isIgnoredMetadata(string $name): bool
    {
        $normalized = str_replace('\\', '/', $name);
        $segments = explode('/', $normalized);
        $basename = end($segments);

        return in_array('__MACOSX', $segments, true)
            || $basename === '.DS_Store'
            || (is_string($basename) && str_starts_with($basename, '.'));
    }
}
