<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Keamanan dan Kebijakan Berkas (DKIS Ticketing)
    |--------------------------------------------------------------------------
    | Sesuai Dokumen Revisi Keamanan Berkas dan Penanganan TLP RED.
    */

    // Disks
    'default_disk' => 'private',
    'vault_disk'   => 'vault',
    'previews_disk' => 'previews',

    // Enkripsi Berkas Rahasia (Vault)
    // Kunci enkripsi terpisah dari APP_KEY untuk isolasi kustodi
    'vault_encryption_key' => env('VAULT_ENCRYPTION_KEY', env('APP_KEY')),

    // Batas Ukuran Berkas (dalam Kilobytes)
    'max_size_kb' => [
        'image'       => 5120,   // 5 MB
        'image_raw'   => 10240,  // 10 MB (opsi tanpa kompresi untuk insiden)
        'pdf'         => 10240,  // 10 MB
        'video'       => 20480,  // 20 MB (MP4 only)
        'text'        => 2048,   // 2 MB (log/txt)
        'default'     => 5120,
    ],

    // Durasi Maksimum Video (detik)
    'video_max_duration_seconds' => 120,

    // Allowlist Ekstensi dan MIME Resmi per Konteks
    'allowed_types' => [
        'jpg'  => ['mime' => 'image/jpeg', 'category' => 'image'],
        'jpeg' => ['mime' => 'image/jpeg', 'category' => 'image'],
        'png'  => ['mime' => 'image/png',  'category' => 'image'],
        'pdf'  => ['mime' => 'application/pdf', 'category' => 'pdf'],
        'txt'  => ['mime' => 'text/plain', 'category' => 'text'],
        'log'  => ['mime' => 'text/plain', 'category' => 'text'],
        'mp4'  => ['mime' => 'video/mp4',  'category' => 'video'],
    ],

    // Format yang DILARANG KERAS di Semua Titik Unggah
    'blocked_extensions' => [
        'doc', 'docx', 'odt', 'rtf', 'xls', 'xlsx', 'ods', 'ppt', 'pptx',
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps', 'phar',
        'exe', 'sh', 'bat', 'bin', 'cmd', 'js', 'html', 'htm', 'svg',
        'zip', 'rar', '7z', 'tar', 'gz', 'iso', 'jar', 'vbs', 'scr', 'dll'
    ],

    // Batas Dimensi Gambar Maksimum (piksel)
    'image' => [
        'max_width'       => 6000,
        'max_height'      => 6000,
        'preview_max_dim' => 1600,
        'thumb_max_dim'   => 300,
        'quality'         => 82,
    ],

    // Konfigurasi Pratinjau PDF
    'pdf_preview' => [
        'max_pages'       => 10,
        'dpi'             => 144,
    ],

    // Antivirus Scanner Feature Flag
    'antivirus' => [
        'enabled' => env('VIRUS_SCAN_ENABLED', false),
        'driver'  => env('VIRUS_SCAN_DRIVER', 'clamav'), // 'clamav' | 'null'
        'clamav_socket' => env('CLAMAV_SOCKET', '/var/run/clamav/clamd.ctl'),
    ],

    // Signed URL Expiration (menit)
    'signed_url_lifetime_minutes' => 5,
];
