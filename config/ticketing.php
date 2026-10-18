<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Bobot Beban Kerja Penugasan Teknisi (Workload Weights)
    |--------------------------------------------------------------------------
    | Perhitungan beban aktif berbobot berdasarkan klasifikasi sensitivitas/kritis TLP:
    | - TLP RED: Beban 3 poin (Insiden Keamanan Siber / Kritis)
    | - TLP AMBER: Beban 2 poin (Urgensi Tinggi / Dampak Luas)
    | - TLP GREEN / Lainnya: Beban 1 poin (Layanan Standar)
    */
    'workload_weights' => [
        'RED' => (int) env('WORKLOAD_WEIGHT_RED', 3),
        'AMBER' => (int) env('WORKLOAD_WEIGHT_AMBER', 2),
        'GREEN' => (int) env('WORKLOAD_WEIGHT_GREEN', 1),
        'DEFAULT' => (int) env('WORKLOAD_WEIGHT_DEFAULT', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Kapasitas Beban Maksimum Standar Teknisi
    |--------------------------------------------------------------------------
    | Nilai default kapasitas beban per teknisi jika belum ditentukan di profil.
    */
    'default_max_load' => (int) env('WORKLOAD_DEFAULT_MAX_LOAD', 6),

    /*
    |--------------------------------------------------------------------------
    | Batas Waktu Respons Penugasan Lapangan (Response SLA Threshold)
    |--------------------------------------------------------------------------
    | Waktu maksimum (dalam menit) bagi teknisi untuk menerima atau menolak penugasan
    | sebelum penugasan otomatis dilepas (unassigned) dan Admin Bidang diberitahu.
    */
    'response_timeout_minutes' => [
        'RED' => (int) env('RESPONSE_TIMEOUT_RED', 15),       // Tiket Kritis: 15 Menit
        'DEFAULT' => (int) env('RESPONSE_TIMEOUT_DEFAULT', 60), // Tiket Umum: 60 Menit
    ],
];
