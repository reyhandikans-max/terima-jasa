<?php
/**
 * Terima Jasa - Application Configuration
 */

return [
    'app_name'    => 'Terima Jasa',
    'app_tagline' => 'Platform Solusi Jasa Teknisi & Servis Terpercaya #1 di Indonesia',
    'app_version' => '1.0.0-alpha',  
    'env'         => 'development', // 'development' or 'production'
    'debug'       => true,
    'timezone'    => 'Asia/Jakarta',
    
    // Defined roles in system
    'roles' => [
        'customer' => [
            'key'         => 'customer',
            'label'       => 'Customer / Pengguna Jasa',
            'description' => 'Mencari teknisi terpercaya, konsultasi AI, dan order layanan servis.'
        ],
        'teknisi' => [
            'key'         => 'teknisi',
            'label'       => 'Mitra Teknisi',
            'description' => 'Menerima order servis, mengelola keahlian, dan memantau penghasilan.'
        ],
        'admin' => [
            'key'         => 'admin',
            'label'       => 'Administrator Sistem',
            'description' => 'Monitoring seluruh transaksi, verifikasi teknisi, dan tata kelola platform.'
        ],
    ],

    // AI Assistant Module Architecture Placeholder
    'ai_service' => [
        'enabled'   => false, // Stage 1 placeholder (will be enabled in future phase)
        'provider'  => 'internal-gemini-service',
        'model'     => 'gemini-1.5-pro',
        'name'      => 'TerimaJasa AI Smart Assistant'
    ]
];
