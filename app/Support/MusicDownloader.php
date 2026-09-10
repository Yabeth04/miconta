<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;

class MusicDownloader
{
    public function status(): array
    {
        $ytDlp = $this->resolveYtDlp();
        $ffmpeg = $this->resolveFfmpeg();

        return [
            'ready' => $ytDlp !== null && $ffmpeg !== null,
            'yt_dlp' => $ytDlp,
            'ffmpeg' => $ffmpeg,
        ];
    }

    /**
     * @return array{path: string, filename: string, title: string, directory: string}
     */
    public function downloadMp3(string $url): array
    {
        $status = $this->status();

        if (! $status['ready']) {
            $missing = [];
            if ($status['yt_dlp'] === null) {
                $missing[] = 'yt-dlp';
            }
            if ($status['ffmpeg'] === null) {
                $missing[] = 'ffmpeg';
            }

            throw new RuntimeException(
                'Faltan herramientas: '.implode(' y ', $missing).'. Ejecutá scripts/install-music-tools.sh'
            );
        }

        $directory = storage_path('app/music/'.Str::uuid()->toString());
        File::ensureDirectoryExists($directory);

        $outputTemplate = $directory.'/%(title).200B.%(ext)s';
        $ffmpegDir = dirname($status['ffmpeg']);

        try {
            $result = Process::timeout((int) config('music.timeout', 300))
                ->run([
                    $status['yt_dlp'],
                    '--no-playlist',
                    '--extract-audio',
                    '--audio-format', 'mp3',
                    '--audio-quality', '0',
                    '--ffmpeg-location', $ffmpegDir,
                    '--newline',
                    '-o', $outputTemplate,
                    '--',
                    $url,
                ]);

            if ($result->failed()) {
                $stderr = trim($result->errorOutput() ?: $result->output());
                $message = $stderr !== ''
                    ? $this->summarizeYtDlpError($stderr)
                    : 'No se pudo descargar el audio.';

                throw new RuntimeException($message);
            }

            $files = collect(File::files($directory))
                ->filter(fn ($file) => strtolower($file->getExtension()) === 'mp3')
                ->values();

            if ($files->isEmpty()) {
                throw new RuntimeException('La descarga terminó pero no se generó un MP3.');
            }

            $file = $files->first();
            $filename = $this->safeFilename($file->getFilename());

            return [
                'path' => $file->getPathname(),
                'filename' => $filename,
                'title' => pathinfo($filename, PATHINFO_FILENAME),
                'directory' => $directory,
            ];
        } catch (\Throwable $e) {
            if (is_dir($directory)) {
                File::deleteDirectory($directory);
            }

            throw $e;
        }
    }

    public function cleanup(string $directory): void
    {
        if ($directory !== '' && str_starts_with($directory, storage_path('app/music')) && is_dir($directory)) {
            File::deleteDirectory($directory);
        }
    }

    private function resolveYtDlp(): ?string
    {
        return $this->resolveBinary(
            config('music.yt_dlp'),
            ['yt-dlp'],
            [base_path('bin/yt-dlp')]
        );
    }

    private function resolveFfmpeg(): ?string
    {
        return $this->resolveBinary(
            config('music.ffmpeg'),
            ['ffmpeg'],
            [base_path('bin/ffmpeg')]
        );
    }

    /**
     * @param  list<string>  $names
     * @param  list<string>  $candidates
     */
    private function resolveBinary(?string $configured, array $names, array $candidates): ?string
    {
        $paths = array_filter([
            $configured,
            ...$candidates,
        ]);

        foreach ($paths as $path) {
            if (is_string($path) && $path !== '' && is_file($path) && is_executable($path)) {
                return $path;
            }
        }

        $finder = new ExecutableFinder;

        foreach ($names as $name) {
            $found = $finder->find($name);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    private function isYoutubeUrl(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return in_array($host, [
            'youtube.com',
            'www.youtube.com',
            'm.youtube.com',
            'music.youtube.com',
            'youtu.be',
            'www.youtu.be',
        ], true);
    }

    private function safeFilename(string $filename): string
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION) ?: 'mp3');
        $name = Str::of($name)
            ->replaceMatches('/[\\\\\/\:\*\?\"\<\>\|]+/u', ' ')
            ->replaceMatches('/\s+/u', ' ')
            ->trim()
            ->limit(180, '')
            ->toString();

        if ($name === '') {
            $name = 'audio';
        }

        return $name.'.'.$ext;
    }

    private function summarizeYtDlpError(string $stderr): string
    {
        $lines = collect(preg_split('/\R/', $stderr) ?: [])
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values();

        $errorLine = $lines->first(fn ($line) => str_contains(strtolower($line), 'error'));

        if ($errorLine) {
            return Str::limit($errorLine, 280);
        }

        return Str::limit($lines->last() ?: 'No se pudo descargar el audio.', 280);
    }
}
