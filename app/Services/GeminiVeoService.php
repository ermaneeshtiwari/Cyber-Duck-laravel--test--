<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiVeoService
{
    private string $baseUrl;
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
        $this->apiKey = (string) config('services.gemini.key', '');
        $this->model = 'veo-3.1-generate-preview';
    }

    /**
     * Start generation (tries referenceImages first, falls back to no refs).
     *
     * $referenceImages: [
     *   ['base64' => '...', 'mimeType' => 'image/jpeg', 'referenceType' => 'asset'],
     *   ...
     * ]
     */
    public function startGeneration(
        string $prompt,
        array $referenceImages = [],
        string $aspectRatio = '16:9',
        int $sampleCount = 1
    ): string {
        $this->ensureApiKeyIsConfigured();

        $payload = $this->buildPayload($prompt, $aspectRatio, $sampleCount, $referenceImages);
        $response = $this->postPredictLongRunning($payload);

        if (! $response->ok() && $this->isReferenceImagesNotSupported($response->status(), $response->body())) {
            Log::warning('VEO: referenceImages not supported; retrying without references', [
                'status' => $response->status(),
                'body' => $response->body(),
                'model' => $this->model,
            ]);

            $payloadNoReferences = $this->buildPayload($prompt, $aspectRatio, $sampleCount, []);
            $response = $this->postPredictLongRunning($payloadNoReferences);
        }

        if (! $response->ok()) {
            throw new \RuntimeException("Veo start failed: {$response->status()} {$response->body()}");
        }

        $name = $response->json('name');
        if (! is_string($name) || $name === '') {
            throw new \RuntimeException("Missing operation name: {$response->body()}");
        }

        return $name;
    }

    public function pollOperation(string $opName): array
    {
        $this->ensureApiKeyIsConfigured();

        $opName = ltrim(trim($opName), '/');
        $opId = $this->normalizeOperationId($opName);

        $headers = ['x-goog-api-key' => $this->apiKey];

        $url1 = "{$this->baseUrl}/operations/{$opId}";
        $response = Http::withHeaders($headers)->get($url1);

        if ($response->status() === 404) {
            $encoded = rawurlencode($opName);
            $url2 = "{$this->baseUrl}/operations/{$encoded}";
            $response = Http::withHeaders($headers)->get($url2);
        }

        if ($response->status() === 404) {
            $url3 = "{$this->baseUrl}/{$opName}";
            $response = Http::withHeaders($headers)->get($url3);

            if ($response->status() === 404) {
                return ['status' => 'running'];
            }
        }

        if (! $response->ok()) {
            return ['status' => 'error', 'message' => "Poll failed: {$response->status()} {$response->body()}"];
        }

        $errorMessage = $response->json('error.message');
        if (is_string($errorMessage) && $errorMessage !== '') {
            return ['status' => 'error', 'message' => $errorMessage];
        }

        if (! $response->json('done')) {
            return ['status' => 'running'];
        }

        $uri =
            $response->json('response.generateVideoResponse.generatedSamples.0.video.uri')
            ?? $response->json('response.generatedVideos.0.video.uri')
            ?? $response->json('response.generatedVideos.0.video')
            ?? $response->json('response.generatedVideos.0.videoFile');

        Log::info('VEO DONE RAW', ['op' => $opName, 'json' => $response->json()]);

        if (! is_string($uri) || $uri === '') {
            return ['status' => 'error', 'message' => "Done but missing video reference: {$response->body()}"];
        }

        return ['status' => 'done', 'download_uri' => $uri];
    }

    public function downloadFromOperation(string $opName): string
    {
        $this->ensureApiKeyIsConfigured();

        $poll = $this->pollOperation($opName);

        if (($poll['status'] ?? null) === 'running') {
            throw new \RuntimeException('Operation still running. Try again later.');
        }

        if (($poll['status'] ?? null) === 'error') {
            throw new \RuntimeException((string) ($poll['message'] ?? 'Operation error'));
        }

        $uri = $poll['download_uri'] ?? null;
        if (! is_string($uri) || $uri === '') {
            throw new \RuntimeException('Operation completed but no download URI was returned.');
        }

        if (str_starts_with($uri, 'files/')) {
            $downloadUrl = "{$this->baseUrl}/{$uri}:download?alt=media";
        } else {
            $downloadUrl = $uri;
        }

        $video = Http::withHeaders([
            'x-goog-api-key' => $this->apiKey,
            'Accept' => 'video/mp4,*/*',
        ])->withOptions(['allow_redirects' => true])->get($downloadUrl);

        if (! $video->ok()) {
            throw new \RuntimeException("Video download failed: {$video->status()} {$video->body()}");
        }

        return $video->body();
    }

    private function postPredictLongRunning(array $payload): Response
    {
        return Http::withHeaders([
            'x-goog-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/models/{$this->model}:predictLongRunning", $payload);
    }

    private function buildPayload(string $prompt, string $aspectRatio, int $sampleCount, array $referenceImages): array
    {
        $payload = [
            'instances' => [
                ['prompt' => $prompt],
            ],
            'parameters' => [
                'aspectRatio' => $aspectRatio,
                'sampleCount' => $sampleCount,
            ],
        ];

        if ($referenceImages === []) {
            return $payload;
        }

        $payload['parameters']['durationSeconds'] = '8';
        $payload['parameters']['personGeneration'] = 'allow_adult';
        $payload['parameters']['referenceImages'] = array_map(function (array $reference): array {
            $base64 = $this->cleanBase64((string) ($reference['base64'] ?? ''));

            return [
                'image' => [
                    'inlineData' => [
                        'mimeType' => (string) ($reference['mimeType'] ?? 'image/jpeg'),
                        'data' => $base64,
                    ],
                ],
                'referenceType' => (string) ($reference['referenceType'] ?? 'asset'),
            ];
        }, array_slice($referenceImages, 0, 3));

        return $payload;
    }

    private function isReferenceImagesNotSupported(int $status, string $body): bool
    {
        if ($status !== 400) {
            return false;
        }

        $normalizedBody = strtolower($body);

        return str_contains($normalizedBody, 'referenceimages')
            && str_contains($normalizedBody, 'supported');
    }

    private function normalizeOperationId(string $opName): string
    {
        $parts = explode('/', trim($opName, '/'));

        return (string) end($parts);
    }

    private function cleanBase64(string $data): string
    {
        $clean = preg_replace('#^data:[^;]+;base64,#', '', trim($data));

        return is_string($clean) && $clean !== '' ? $clean : $data;
    }

    private function ensureApiKeyIsConfigured(): void
    {
        if ($this->apiKey === '') {
            throw new \RuntimeException('Gemini API key is not configured. Set services.gemini.key.');
        }
    }
}
