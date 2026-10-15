<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanStorageTemp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:clean-tmp {--hours=2 : Delete files older than this many hours}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up abandoned temporary uploads from storage (local and Vercel Blob)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $cutoffTimestamp = now()->subHours($hours)->timestamp;

        $this->info("Cleaning temporary files older than {$hours} hours (before ".date('Y-m-d H:i:s', $cutoffTimestamp).")...");

        $deletedCount = 0;

        // 1. Clean local / server storage temporary files
        $deletedCount += $this->cleanLocalStorage($cutoffTimestamp);

        // 2. Clean Vercel Blob temporary files (livewire-tmp/)
        $token = env('BLOB_READ_WRITE_TOKEN');
        if (! empty($token)) {
            $deletedCount += $this->cleanVercelBlob($token, $cutoffTimestamp);
        }

        $this->info("Cleanup completed. Total files deleted: {$deletedCount}");
        Log::info("Storage temp cleanup executed. Deleted files: {$deletedCount}");

        return Command::SUCCESS;
    }

    protected function cleanLocalStorage(int $cutoffTimestamp): int
    {
        $count = 0;
        $localPaths = [
            storage_path('app/livewire-tmp'),
            storage_path('app/private/livewire-tmp'),
            sys_get_temp_dir(),
        ];

        foreach ($localPaths as $dir) {
            if (! is_dir($dir)) {
                continue;
            }

            try {
                $files = scandir($dir);
                if ($files === false) {
                    continue;
                }

                foreach ($files as $file) {
                    if ($file === '.' || $file === '..' || $file === '.gitignore') {
                        continue;
                    }

                    // For sys_get_temp_dir, only delete php/livewire temporary files
                    if ($dir === sys_get_temp_dir() && ! str_starts_with($file, 'livewire') && ! str_starts_with($file, 'php')) {
                        continue;
                    }

                    $filePath = $dir.DIRECTORY_SEPARATOR.$file;
                    if (is_file($filePath) && filemtime($filePath) < $cutoffTimestamp) {
                        if (@unlink($filePath)) {
                            $count++;
                        }
                    }
                }
            } catch (\Throwable $e) {
                $this->warn("Local directory cleanup warning for {$dir}: ".$e->getMessage());
            }
        }

        return $count;
    }

    public static function cleanVercelBlobWithDetails(string $token, int $cutoffTimestamp): array
    {
        $deletedUrls = [];
        $cursor = null;
        $hasMore = true;

        while ($hasMore) {
            $params = [
                'prefix' => 'livewire-tmp/',
                'limit' => 1000,
            ];
            if ($cursor) {
                $params['cursor'] = $cursor;
            }

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'x-api-version' => '12',
            ])->get('https://blob.vercel-storage.com/?'.http_build_query($params));

            if (! $response->successful()) {
                Log::error('Vercel Blob list error: '.$response->body());
                break;
            }

            $data = $response->json();
            $blobs = $data['blobs'] ?? [];
            $hasMore = $data['hasMore'] ?? false;
            $cursor = $data['cursor'] ?? null;

            $batchToDelete = [];
            foreach ($blobs as $blob) {
                $uploadedAt = isset($blob['uploadedAt']) ? strtotime($blob['uploadedAt']) : 0;
                if ($uploadedAt > 0 && $uploadedAt < $cutoffTimestamp) {
                    if (! empty($blob['url'])) {
                        $batchToDelete[] = $blob['url'];
                    }
                }
            }

            if (! empty($batchToDelete)) {
                $delResponse = Http::withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'x-api-version' => '12',
                    'Content-Type' => 'application/json',
                ])->post('https://blob.vercel-storage.com/delete', [
                    'urls' => array_values(array_unique($batchToDelete)),
                ]);

                if ($delResponse->successful()) {
                    $deletedUrls = array_merge($deletedUrls, $batchToDelete);
                } else {
                    Log::error('Vercel Blob delete error: '.$delResponse->body());
                }
            }
        }

        return $deletedUrls;
    }

    protected function cleanVercelBlob(string $token, int $cutoffTimestamp): int
    {
        $deleted = static::cleanVercelBlobWithDetails($token, $cutoffTimestamp);
        $this->info('Vercel Blob livewire-tmp cleaned: '.count($deleted).' files.');

        return count($deleted);
    }
}
