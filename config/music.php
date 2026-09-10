<?php

return [
    /*
    |--------------------------------------------------------------------------
    | yt-dlp / FFmpeg binaries
    |--------------------------------------------------------------------------
    |
    | Absolute paths preferred. If empty, the app looks in base_path('bin')
    | and then in the system PATH.
    |
    */
    'yt_dlp' => env('MUSIC_YT_DLP_PATH', ''),
    'ffmpeg' => env('MUSIC_FFMPEG_PATH', ''),
    'ffprobe' => env('MUSIC_FFPROBE_PATH', ''),

    'timeout' => (int) env('MUSIC_DOWNLOAD_TIMEOUT', 300),
];
