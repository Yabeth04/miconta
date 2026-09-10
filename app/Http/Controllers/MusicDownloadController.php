<?php

namespace App\Http\Controllers;

use App\Support\MusicDownloader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class MusicDownloadController extends Controller
{
    public function status(MusicDownloader $downloader)
    {
        return response()->json($downloader->status());
    }

    public function download(Request $request, MusicDownloader $downloader): BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:2048', 'url', 'regex:/^https?:\/\//i'],
        ], [
            'url.required' => 'Pegá una URL.',
            'url.url' => 'La URL no es válida.',
            'url.regex' => 'Solo se permiten URLs http/https.',
        ]);

        set_time_limit((int) config('music.timeout', 300) + 30);

        $artifact = null;

        try {
            $artifact = $downloader->downloadMp3($validated['url']);

            $directory = $artifact['directory'];

            app()->terminating(function () use ($downloader, $directory) {
                $downloader->cleanup($directory);
            });

            return response()
                ->download($artifact['path'], $artifact['filename'], [
                    'Content-Type' => 'audio/mpeg',
                ])
                ->deleteFileAfterSend(true);
        } catch (RuntimeException $e) {
            if (is_array($artifact)) {
                $downloader->cleanup($artifact['directory']);
            }

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            if (is_array($artifact)) {
                $downloader->cleanup($artifact['directory']);
            }

            Log::error('Music download failed', [
                'url' => $validated['url'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Ocurrió un error al descargar el audio.',
            ], 500);
        }
    }
}
