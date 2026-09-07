<?php

namespace App\Services\Storage;

use Illuminate\Support\Facades\Http;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;

class VercelBlobAdapter implements FilesystemAdapter, PublicUrlGenerator
{
    protected string $token;
    protected string $storeId;

    public function __construct(?string $token = null, ?string $storeId = null)
    {
        $this->token = $token ?: (string) env('BLOB_READ_WRITE_TOKEN', '');
        $this->storeId = $storeId ?: (string) env('BLOB_STORE_ID', '');
    }

    public function getUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $path = ltrim($path, '/');
        if (!empty($this->storeId)) {
            return "https://{$this->storeId}.public.blob.vercel-storage.com/{$path}";
        }

        return "https://blob.vercel-storage.com/{$path}";
    }

    public function publicUrl(string $path, Config $config): string
    {
        return $this->getUrl($path);
    }

    public function fileExists(string $path): bool
    {
        $url = $this->getUrl($path);
        try {
            $response = Http::head($url);
            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function directoryExists(string $path): bool
    {
        return true;
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $path = ltrim($path, '/');
        $endpoint = "https://blob.vercel-storage.com/{$path}";

        $mime = $config->get('ContentType') ?: $config->get('mimetype');
        if (!$mime) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mimes = [
                'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png', 'webp' => 'image/webp',
                'svg' => 'image/svg+xml', 'gif' => 'image/gif',
                'pdf' => 'application/pdf', 'txt' => 'text/plain',
            ];
            $mime = $mimes[$ext] ?? 'application/octet-stream';
        }

        $headers = [
            'Authorization' => "Bearer {$this->token}",
            'x-api-version' => '7',
            'Content-Type' => $mime,
        ];

        // Attempt deterministic path first
        $response = Http::withHeaders($headers)
            ->withBody($contents, $mime)
            ->put("{$endpoint}?addRandomSuffix=false");

        if (!$response->successful()) {
            $response = Http::withHeaders($headers)
                ->withBody($contents, $mime)
                ->put($endpoint);
        }

        if (!$response->successful()) {
            throw UnableToWriteFile::atLocation($path, $response->body());
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $data = stream_get_contents($contents);
        $this->write($path, $data, $config);
    }

    public function read(string $path): string
    {
        $url = $this->getUrl($path);
        $response = Http::get($url);

        if (!$response->successful()) {
            throw UnableToReadFile::fromLocation($path, $response->body());
        }

        return $response->body();
    }

    public function readStream(string $path)
    {
        $data = $this->read($path);
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, $data);
        rewind($stream);
        return $stream;
    }

    public function delete(string $path): void
    {
        $url = $this->getUrl($path);

        try {
            Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'x-api-version' => '7',
                'Content-Type' => 'application/json',
            ])->post('https://blob.vercel-storage.com/delete', [
                'urls' => [$url],
            ]);
        } catch (\Throwable) {
            // Delete best-effort
        }
    }

    public function deleteDirectory(string $path): void
    {
        // Flat object store
    }

    public function createDirectory(string $path, Config $config): void
    {
        // Flat object store
    }

    public function setVisibility(string $path, string $visibility): void
    {
        // Public store
    }

    public function visibility(string $path): FileAttributes
    {
        return new FileAttributes($path, visibility: 'public');
    }

    public function mimeType(string $path): FileAttributes
    {
        $url = $this->getUrl($path);
        try {
            $response = Http::head($url);
            $mime = $response->header('Content-Type') ?: 'application/octet-stream';
            return new FileAttributes($path, mimeType: $mime);
        } catch (\Throwable) {
            return new FileAttributes($path, mimeType: 'application/octet-stream');
        }
    }

    public function lastModified(string $path): FileAttributes
    {
        $url = $this->getUrl($path);
        try {
            $response = Http::head($url);
            $lastMod = $response->header('Last-Modified') ? strtotime($response->header('Last-Modified')) : time();
            return new FileAttributes($path, lastModified: $lastMod);
        } catch (\Throwable) {
            return new FileAttributes($path, lastModified: time());
        }
    }

    public function fileSize(string $path): FileAttributes
    {
        $url = $this->getUrl($path);
        try {
            $response = Http::head($url);
            $size = (int) $response->header('Content-Length', 0);
            return new FileAttributes($path, fileSize: $size);
        } catch (\Throwable) {
            return new FileAttributes($path, fileSize: 0);
        }
    }

    public function listContents(string $path, bool $deep): iterable
    {
        return [];
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $this->copy($source, $destination, $config);
        $this->delete($source);
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $contents = $this->read($source);
        $this->write($destination, $contents, $config);
    }
}
