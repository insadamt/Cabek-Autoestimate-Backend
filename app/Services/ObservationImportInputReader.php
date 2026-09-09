<?php

namespace App\Services;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

class ObservationImportInputReader
{
    private array $openArchives = [];

    public function __destruct()
    {
        foreach ($this->openArchives as $zip) {
            $zip->close();
        }
    }

    public function discover(string $inputPath): array
    {
        $resolvedPath = realpath($inputPath);

        if ($resolvedPath === false) {
            throw new RuntimeException('Input path does not exist: '.$inputPath);
        }

        if (is_dir($resolvedPath)) {
            return $this->discoverDirectory($resolvedPath);
        }

        $extension = strtolower(pathinfo($resolvedPath, PATHINFO_EXTENSION));

        return match ($extension) {
            'json' => [$this->jsonDescriptor($resolvedPath)],
            'zip' => $this->discoverZip($resolvedPath),
            default => throw new RuntimeException('Input must be a JSON file, ZIP archive, or directory containing JSON batches.'),
        };
    }

    public function read(array $descriptor): string
    {
        return match ($descriptor['type'] ?? null) {
            'file' => $this->readFile($descriptor),
            'zip' => $this->readZipEntry($descriptor),
            default => throw new RuntimeException('Unsupported import input descriptor.'),
        };
    }

    private function discoverDirectory(string $directory): array
    {
        $files = [];
        $totalBytes = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            if (strtolower($file->getExtension()) !== 'json') {
                continue;
            }

            if ($this->isIgnoredJson($file->getFilename())) {
                continue;
            }

            $descriptor = $this->jsonDescriptor($file->getRealPath());
            $totalBytes += (int) $descriptor['size'];

            if ($totalBytes > (int) config('observation_imports.max_uncompressed_bytes')) {
                throw new RuntimeException('The directory exceeds the configured total JSON size limit.');
            }

            $files[] = $descriptor;
        }

        usort($files, fn (array $left, array $right): int => strcmp($left['name'], $right['name']));

        $this->assertFileCount($files);

        if ($files === []) {
            throw new RuntimeException('The directory does not contain any import-ready JSON files.');
        }

        return $files;
    }

    private function discoverZip(string $archivePath): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('ZIP imports require the PHP zip extension.');
        }

        $archiveSize = filesize($archivePath);
        $maxArchiveBytes = (int) config('observation_imports.max_archive_kb') * 1024;

        if ($archiveSize !== false && $archiveSize > $maxArchiveBytes) {
            throw new RuntimeException('The ZIP archive exceeds the configured archive size limit.');
        }

        $zip = new ZipArchive;
        $openResult = $zip->open($archivePath);

        if ($openResult !== true) {
            throw new RuntimeException('The input file is not a readable ZIP archive.');
        }

        $descriptors = [];
        $declaredUncompressedBytes = 0;

        try {
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index);

                if (! is_array($stat) || ! isset($stat['name'])) {
                    throw new RuntimeException('The ZIP archive contains an unreadable entry.');
                }

                $name = (string) $stat['name'];
                $normalized = str_replace('\\', '/', $name);

                if (! $this->isSafeEntryName($normalized)) {
                    throw new RuntimeException('The ZIP archive contains an unsafe path: '.$name);
                }

                if (str_ends_with($normalized, '/') || $this->isIgnoredMetadata($normalized)) {
                    continue;
                }

                if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'json') {
                    throw new RuntimeException('ZIP archives may contain only JSON files. Unexpected entry: '.$name);
                }

                if ($this->isIgnoredJson(basename($normalized))) {
                    continue;
                }

                $declaredBytes = (int) ($stat['size'] ?? 0);
                $this->assertJsonSize($declaredBytes, $name);

                $declaredUncompressedBytes += $declaredBytes;

                if ($declaredUncompressedBytes > (int) config('observation_imports.max_uncompressed_bytes')) {
                    throw new RuntimeException('The ZIP archive expands beyond the configured uncompressed size limit.');
                }

                $descriptors[] = [
                    'type' => 'zip',
                    'name' => $name,
                    'archive_path' => $archivePath,
                    'size' => $declaredBytes,
                ];
            }
        } finally {
            $zip->close();
        }

        $this->assertFileCount($descriptors);

        if ($descriptors === []) {
            throw new RuntimeException('The ZIP archive does not contain any import-ready JSON files.');
        }

        return $descriptors;
    }

    private function jsonDescriptor(string $path): array
    {
        $size = filesize($path);

        if ($size === false) {
            throw new RuntimeException('Could not determine JSON file size: '.$path);
        }

        $this->assertJsonSize($size, basename($path));

        return [
            'type' => 'file',
            'name' => $path,
            'path' => $path,
            'size' => $size,
        ];
    }

    private function readFile(array $descriptor): string
    {
        $contents = file_get_contents($descriptor['path']);

        if ($contents === false) {
            throw new RuntimeException('Could not read '.$descriptor['name'].'.');
        }

        $this->assertJsonSize(strlen($contents), $descriptor['name']);

        return $contents;
    }

    private function readZipEntry(array $descriptor): string
    {
        $zip = $this->openArchive($descriptor['archive_path']);
        $stream = $zip->getStream($descriptor['name']);

        if (! is_resource($stream)) {
            throw new RuntimeException('Could not read '.$descriptor['name'].' from the ZIP archive.');
        }

        $contents = '';
        $maxJsonBytes = (int) config('observation_imports.max_json_bytes');

        try {
            while (! feof($stream)) {
                $chunk = fread($stream, 8192);

                if ($chunk === false) {
                    throw new RuntimeException('Could not read '.$descriptor['name'].' from the ZIP archive.');
                }

                $contents .= $chunk;

                if (strlen($contents) > $maxJsonBytes) {
                    throw new RuntimeException($descriptor['name'].' exceeds the configured JSON file size limit.');
                }
            }
        } finally {
            fclose($stream);
        }

        return $contents;
    }

    private function openArchive(string $archivePath): ZipArchive
    {
        if (isset($this->openArchives[$archivePath])) {
            return $this->openArchives[$archivePath];
        }

        $zip = new ZipArchive;
        $openResult = $zip->open($archivePath);

        if ($openResult !== true) {
            throw new RuntimeException('Could not open ZIP archive: '.$archivePath);
        }

        return $this->openArchives[$archivePath] = $zip;
    }

    private function assertJsonSize(int $bytes, string $name): void
    {
        if ($bytes > (int) config('observation_imports.max_json_bytes')) {
            throw new RuntimeException($name.' exceeds the configured JSON file size limit.');
        }
    }

    private function assertFileCount(array $files): void
    {
        $maxFiles = (int) config('observation_imports.max_json_files');

        if (count($files) > $maxFiles) {
            throw new RuntimeException('Input contains more than '.$maxFiles.' JSON files.');
        }
    }

    private function isIgnoredJson(string $basename): bool
    {
        return strtolower($basename) === 'normalization_review.json';
    }

    private function isSafeEntryName(string $name): bool
    {
        if ($name === '' || str_contains($name, "\0") || str_starts_with($name, '/')) {
            return false;
        }

        if (preg_match('/^[A-Za-z]:\//', $name) === 1) {
            return false;
        }

        foreach (explode('/', $name) as $segment) {
            if ($segment === '..') {
                return false;
            }
        }

        return true;
    }

    private function isIgnoredMetadata(string $name): bool
    {
        $segments = explode('/', $name);
        $basename = end($segments);

        return in_array('__MACOSX', $segments, true)
            || $basename === '.DS_Store'
            || (is_string($basename) && str_starts_with($basename, '.'));
    }
}
