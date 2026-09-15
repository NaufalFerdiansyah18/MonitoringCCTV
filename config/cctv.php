<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mode Kamera
    |--------------------------------------------------------------------------
    |
    | local  : gunakan ip_local + port_local.
    | public : gunakan ip_public + port_public (URL tidak dibangkitkan bila
    |          ip_public kosong).
    |
    */

    'mode' => env('CCTV_MODE', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Subtype Stream Dahua
    |--------------------------------------------------------------------------
    |
    | 0 = stream utama, 1 = substream (hemat bandwidth untuk grid).
    |
    */

    'subtype' => env('CCTV_SUBTYPE', 0),

    /*
    |--------------------------------------------------------------------------
    | Batas Streaming Bersamaan
    |--------------------------------------------------------------------------
    |
    | Jumlah maksimum stream yang boleh berjalan sekaligus
    | (1 proses FFmpeg per kamera).
    |
    */

    'max_concurrent_streams' => (int) env('MAX_CONCURRENT_STREAMS', 8),

    /*
    |--------------------------------------------------------------------------
    | Path Binary FFmpeg
    |--------------------------------------------------------------------------
    |
    | Dipakai oleh StreamManager (multi-stream) saat membangkitkan HLS.
    |
    */

    'ffmpeg_path' => env('FFMPEG_PATH', 'ffmpeg'),

];
