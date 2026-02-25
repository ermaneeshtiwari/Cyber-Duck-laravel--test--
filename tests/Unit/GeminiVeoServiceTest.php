<?php

namespace Tests\Unit;

use App\Services\GeminiVeoService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiVeoServiceTest extends TestCase
{
    public function test_start_generation_falls_back_when_reference_images_are_not_supported(): void
    {
        config(['services.gemini.key' => 'test-key']);

        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*:predictLongRunning' => Http::sequence()
                ->push('`referenceImages` is not supported', 400)
                ->push(['name' => 'operations/abc123'], 200),
        ]);

        $service = new GeminiVeoService();

        $name = $service->startGeneration(
            'A cinematic waterfall scene',
            [['base64' => 'data:image/jpeg;base64,aGVsbG8=', 'mimeType' => 'image/jpeg']]
        );

        $this->assertSame('operations/abc123', $name);

        Http::assertSentCount(2);
        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), ':predictLongRunning')) {
                return false;
            }

            return isset($request['parameters']['referenceImages']);
        });
        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), ':predictLongRunning')) {
                return false;
            }

            return ! isset($request['parameters']['referenceImages']);
        });
    }

    public function test_poll_operation_returns_done_with_video_uri(): void
    {
        config(['services.gemini.key' => 'test-key']);

        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/operations/*' => Http::response([
                'done' => true,
                'response' => [
                    'generatedVideos' => [
                        ['video' => ['uri' => 'files/my-video']],
                    ],
                ],
            ], 200),
        ]);

        $service = new GeminiVeoService();

        $status = $service->pollOperation('operations/op-1');

        $this->assertSame(['status' => 'done', 'download_uri' => 'files/my-video'], $status);
    }

    public function test_download_from_operation_resolves_files_uri_to_download_endpoint(): void
    {
        config(['services.gemini.key' => 'test-key']);

        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/operations/*' => Http::response([
                'done' => true,
                'response' => [
                    'generatedVideos' => [
                        ['video' => ['uri' => 'files/video123']],
                    ],
                ],
            ], 200),
            'https://generativelanguage.googleapis.com/v1beta/files/video123:download?alt=media' => Http::response('mp4-binary', 200),
        ]);

        $service = new GeminiVeoService();

        $videoContent = $service->downloadFromOperation('operations/op-2');

        $this->assertSame('mp4-binary', $videoContent);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://generativelanguage.googleapis.com/v1beta/files/video123:download?alt=media';
        });
    }
}
