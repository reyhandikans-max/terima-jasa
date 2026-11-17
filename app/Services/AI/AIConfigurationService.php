<?php
namespace App\Services\AI;

use App\Core\Model;
use App\Models\AiProviderConfig;
use App\Models\AiFeatureConfig;
use App\Models\AiConnectionLog;

/**
 * AIConfigurationService
 * Layanan Pusat Manajemen Konfigurasi AI, Enkripsi Kredensial, SSRF Guard, dan Resolusi Model per Fitur
 */
class AIConfigurationService
{
    private const CIPHER_METHOD = 'AES-256-CBC';

    /**
     * Pastikan tabel-tabel AI dan data bawaan sudah tersedia di database secara otomatis
     */
    public static function ensureTablesExist(): void
    {
        static $checked = false;
        if ($checked) return;

        $db = Model::getDb();
        if (!$db) return;

        try {
            // 1. Tabel ai_provider_configs
            $db->exec("
                CREATE TABLE IF NOT EXISTS `ai_provider_configs` (
                    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(150) NOT NULL,
                    `provider` VARCHAR(50) NOT NULL,
                    `base_url` VARCHAR(255) NULL,
                    `api_key_encrypted` TEXT NULL,
                    `default_model` VARCHAR(100) NOT NULL,
                    `temperature` DECIMAL(3,2) NOT NULL DEFAULT 0.30,
                    `max_output_tokens` INT UNSIGNED NOT NULL DEFAULT 800,
                    `top_p` DECIMAL(3,2) NOT NULL DEFAULT 1.00,
                    `timeout_seconds` INT UNSIGNED NOT NULL DEFAULT 15,
                    `retry_count` TINYINT UNSIGNED NOT NULL DEFAULT 2,
                    `is_active` TINYINT(1) NOT NULL DEFAULT 0,
                    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX `idx_ai_provider_active` (`is_active`),
                    INDEX `idx_ai_provider_type` (`provider`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 2. Tabel ai_feature_configs
            $db->exec("
                CREATE TABLE IF NOT EXISTS `ai_feature_configs` (
                    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `feature_key` VARCHAR(50) NOT NULL UNIQUE,
                    `feature_label` VARCHAR(100) NOT NULL,
                    `provider_config_id` BIGINT UNSIGNED NULL,
                    `model_name` VARCHAR(100) NULL,
                    `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
                    `custom_parameters` JSON NULL,
                    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    CONSTRAINT `fk_ai_feature_provider` 
                        FOREIGN KEY (`provider_config_id`) REFERENCES `ai_provider_configs` (`id`) 
                        ON DELETE SET NULL ON UPDATE CASCADE,
                    INDEX `idx_ai_feature_key` (`feature_key`),
                    INDEX `idx_ai_feature_enabled` (`is_enabled`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 3. Tabel ai_connection_logs
            $db->exec("
                CREATE TABLE IF NOT EXISTS `ai_connection_logs` (
                    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `provider_config_id` BIGINT UNSIGNED NULL,
                    `provider_name` VARCHAR(50) NOT NULL,
                    `model_tested` VARCHAR(100) NOT NULL,
                    `status` ENUM('success', 'failed') NOT NULL,
                    `latency_ms` INT UNSIGNED NOT NULL DEFAULT 0,
                    `error_code` VARCHAR(100) NULL,
                    `error_message` VARCHAR(255) NULL,
                    `tested_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `tested_by` BIGINT UNSIGNED NULL,
                    INDEX `idx_ai_log_provider` (`provider_config_id`),
                    INDEX `idx_ai_log_status` (`status`),
                    INDEX `idx_ai_log_tested_at` (`tested_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // Seed default active provider dari .env jika belum ada konfigurasi sama sekali
            $count = (int)$db->query("SELECT COUNT(*) FROM `ai_provider_configs`")->fetchColumn();
            if ($count === 0) {
                $rawKey = (string)env('AI_API_KEY', env('GROQ_API_KEY', ''));
                $encryptedKey = !empty($rawKey) ? self::encryptApiKey($rawKey) : null;
                $provider = (string)env('AI_PROVIDER', 'groq');
                $model = (string)env('AI_MODEL', 'llama-3.3-70b-versatile');
                $temp = (float)env('AI_TEMPERATURE', 0.3);
                $maxTokens = (int)env('AI_MAX_TOKENS', 800);

                $stmt = $db->prepare("
                    INSERT INTO `ai_provider_configs` 
                    (`name`, `provider`, `base_url`, `api_key_encrypted`, `default_model`, `temperature`, `max_output_tokens`, `top_p`, `timeout_seconds`, `retry_count`, `is_active`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1.00, 15, 2, 1)
                ");
                $stmt->execute([
                    'AI Utama Terima Jasa',
                    $provider,
                    'https://api.groq.com/openai/v1',
                    $encryptedKey,
                    $model,
                    $temp,
                    $maxTokens
                ]);
            }

            // Seed feature configs jika belum ada
            $featCount = (int)$db->query("SELECT COUNT(*) FROM `ai_feature_configs`")->fetchColumn();
            if ($featCount === 0) {
                $features = [
                    ['chatbot', 'Chatbot AI Asisten Customer & Teknisi'],
                    ['complaint_analysis', 'Analisis Keluhan Customer & Deteksi Kerusakan'],
                    ['service_recommendation', 'Rekomendasi Katalog Layanan Jasa'],
                    ['technician_recommendation', 'Rekomendasi Mitra Teknisi Terverifikasi'],
                    ['cost_estimation', 'Estimasi Biaya Perbaikan'],
                    ['duration_estimation', 'Estimasi Durasi Pengerjaan'],
                    ['troubleshooting', 'Panduan Troubleshooting & SOP K3 Teknisi'],
                    ['conversation_summary', 'Ringkasan Percakapan Konsultasi'],
                ];
                $stmt = $db->prepare("INSERT IGNORE INTO `ai_feature_configs` (`feature_key`, `feature_label`, `is_enabled`) VALUES (?, ?, 1)");
                foreach ($features as $f) {
                    $stmt->execute([$f[0], $f[1]]);
                }
            }

            $checked = true;
        } catch (\Throwable $e) {
            // Biarkan lewat jika tabel sudah ada atau ada kendala koneksi sementara
        }
    }

    /**
     * Dapatkan kunci enkripsi dari environment (32 bytes)
     */
    private static function getEncryptionKey(): string
    {
        $rawKey = (string)env('AI_CONFIG_ENCRYPTION_KEY', env('APP_KEY', ''));
        if (empty($rawKey)) {
            // Fallback deterministic key khusus jika env belum diisi
            $rawKey = hash('sha256', 'terima_jasa_internal_ai_secure_salt_2026');
        }
        return hash('sha256', $rawKey, true);
    }

    /**
     * Enkripsi API Key secara aman menggunakan AES-256-CBC
     */
    public static function encryptApiKey(string $plain): string
    {
        if (empty(trim($plain))) return '';

        $key = self::getEncryptionKey();
        $iv = random_bytes(openssl_cipher_iv_length(self::CIPHER_METHOD));
        $encrypted = openssl_encrypt($plain, self::CIPHER_METHOD, $key, 0, $iv);

        return base64_encode($iv . $encrypted);
    }

    /**
     * Dekripsi API Key dari database
     */
    public static function decryptApiKey(?string $encryptedPayload): string
    {
        if (empty($encryptedPayload)) return '';

        $data = base64_decode($encryptedPayload, true);
        if ($data === false) return '';

        $ivLen = openssl_cipher_iv_length(self::CIPHER_METHOD);
        if (strlen($data) <= $ivLen) return '';

        $iv = substr($data, 0, $ivLen);
        $ciphertext = substr($data, $ivLen);
        $key = self::getEncryptionKey();

        $decrypted = openssl_decrypt($ciphertext, self::CIPHER_METHOD, $key, 0, $iv);
        return ($decrypted !== false) ? $decrypted : '';
    }

    /**
     * Proteksi SSRF: Validasi URL Base Endpoint agar tidak mengakses jaringan internal atau metadata cloud
     */
    public static function validateBaseUrl(string $url): array
    {
        $url = trim($url);
        if (empty($url)) {
            return ['valid' => false, 'error' => 'Base URL tidak boleh kosong.'];
        }

        $parsed = parse_url($url);
        if (!isset($parsed['scheme']) || !in_array(strtolower($parsed['scheme']), ['http', 'https'])) {
            return ['valid' => false, 'error' => 'Protokol URL harus berupa HTTP atau HTTPS.'];
        }

        $host = strtolower($parsed['host'] ?? '');
        if (empty($host)) {
            return ['valid' => false, 'error' => 'Format URL tidak valid. Host tidak ditemukan.'];
        }

        // Blokir domain dan host metadata cloud berbahaya
        $blockedHosts = [
            'localhost',
            '127.0.0.1',
            '::1',
            '0.0.0.0',
            'instance-data',
            'metadata.google.internal',
            'metadata.packet.net',
        ];

        if (in_array($host, $blockedHosts)) {
            return ['valid' => false, 'error' => 'Keamanan SSRF: Host internal/loopback tidak diizinkan untuk keamanan server Terima Jasa.'];
        }

        // Validasi jika host langsung berupa alamat IP
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (!filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return ['valid' => false, 'error' => "Keamanan SSRF: IP {$host} adalah IP internal/private/reserved yang tidak diizinkan."];
            }
        } else {
            // Resolusi domain ke IP address
            $ip = gethostbyname($host);
            if ($ip !== $host && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return ['valid' => false, 'error' => "Keamanan SSRF: Host {$host} mengarah ke IP internal/private ({$ip}) yang tidak diizinkan."];
            }
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Dapatkan Konfigurasi AI Provider yang Aktif
     */
    public static function getActiveConfig(): ?AiProviderConfig
    {
        self::ensureTablesExist();

        $active = AiProviderConfig::getActive();
        if ($active) {
            return $active;
        }

        // Fallback dinamis dari environment jika database belum memiliki baris aktif
        return new AiProviderConfig([
            'id'                => 0,
            'name'              => 'Default Environment Provider',
            'provider'          => (string)env('AI_PROVIDER', 'groq'),
            'base_url'          => 'https://api.groq.com/openai/v1',
            'api_key_encrypted' => self::encryptApiKey((string)env('AI_API_KEY', env('GROQ_API_KEY', ''))),
            'default_model'     => (string)env('AI_MODEL', 'llama-3.3-70b-versatile'),
            'temperature'       => (float)env('AI_TEMPERATURE', 0.3),
            'max_output_tokens' => (int)env('AI_MAX_TOKENS', 800),
            'top_p'             => 1.0,
            'timeout_seconds'   => 15,
            'retry_count'       => 2,
            'is_active'         => 1
        ]);
    }

    /**
     * Resolusi Konfigurasi Model Spesifik untuk Fitur Tertentu
     *
     * @param string $featureKey Contoh: 'chatbot', 'complaint_analysis', 'service_recommendation', dll.
     * @return array|null ['enabled' => bool, 'provider_adapter' => AIProviderInterface, 'model' => string, 'temperature' => float, 'max_tokens' => int]
     */
    public static function getFeatureConfig(string $featureKey): ?array
    {
        self::ensureTablesExist();

        $activeProvider = self::getActiveConfig();
        if (!$activeProvider || (int)$activeProvider->is_active !== 1) {
            return [
                'enabled'          => false,
                'disabled_reason'  => 'Layanan AI dinonaktifkan secara global oleh Administrator.',
                'provider_adapter' => null,
                'model'            => null,
            ];
        }

        $feat = AiFeatureConfig::findByKey($featureKey);
        if ($feat && (int)$feat->is_enabled === 0) {
            return [
                'enabled'          => false,
                'disabled_reason'  => "Fitur {$feat->feature_label} sedang dinonaktifkan oleh Administrator.",
                'provider_adapter' => null,
                'model'            => null,
            ];
        }

        // Tentukan provider config (jika fitur memiliki provider override)
        $chosenProviderConfig = $activeProvider;
        if ($feat && !empty($feat->provider_config_id)) {
            $specificProvider = AiProviderConfig::find($feat->provider_config_id);
            if ($specificProvider) {
                $chosenProviderConfig = $specificProvider;
            }
        }

        // Tentukan model name
        $model = (!empty($feat->model_name)) ? $feat->model_name : $chosenProviderConfig->default_model;

        // Custom parameters
        $customParams = [];
        if ($feat && !empty($feat->custom_parameters)) {
            $customParams = is_array($feat->custom_parameters) ? $feat->custom_parameters : (json_decode($feat->custom_parameters, true) ?: []);
        }

        $temperature = isset($customParams['temperature']) ? (float)$customParams['temperature'] : (float)$chosenProviderConfig->temperature;
        $maxTokens = isset($customParams['max_output_tokens']) ? (int)$customParams['max_output_tokens'] : (int)$chosenProviderConfig->max_output_tokens;

        // Dekripsi API key
        $apiKey = self::decryptApiKey($chosenProviderConfig->api_key_encrypted);
        if (empty($apiKey)) {
            $apiKey = (string)env('AI_API_KEY', env('GROQ_API_KEY', ''));
        }

        // Instansiasi Provider Adapter
        $adapter = AIProviderManager::create($chosenProviderConfig->provider, [
            'api_key'           => $apiKey,
            'base_url'          => $chosenProviderConfig->base_url,
            'default_model'     => $model,
            'temperature'       => $temperature,
            'max_output_tokens' => $maxTokens,
            'top_p'             => (float)$chosenProviderConfig->top_p,
            'timeout_seconds'   => (int)$chosenProviderConfig->timeout_seconds,
        ]);

        return [
            'enabled'          => true,
            'provider_adapter' => $adapter,
            'model'            => $model,
            'temperature'      => $temperature,
            'max_tokens'       => $maxTokens,
            'timeout'          => (int)$chosenProviderConfig->timeout_seconds,
        ];
    }

    /**
     * Catat log audit pengujian koneksi ke database secara aman
     */
    public static function logConnectionTest(array $data): void
    {
        self::ensureTablesExist();

        $db = Model::getDb();
        if (!$db) return;

        try {
            $stmt = $db->prepare("
                INSERT INTO `ai_connection_logs` 
                (`provider_config_id`, `provider_name`, `model_tested`, `status`, `latency_ms`, `error_code`, `error_message`, `tested_by`, `tested_at`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $data['provider_config_id'] ?? null,
                $data['provider_name'] ?? 'unknown',
                $data['model_tested'] ?? 'default',
                $data['status'] === 'success' ? 'success' : 'failed',
                (int)($data['latency_ms'] ?? 0),
                $data['error_code'] ?? null,
                substr((string)($data['error_message'] ?? ''), 0, 255),
                $data['tested_by'] ?? null,
            ]);
        } catch (\Throwable $e) {
            // Hindari exception fatal bila logging bermasalah
        }
    }
}
