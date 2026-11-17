<?php
/**
 * Migration: Create AI Configuration & Audit Log Tables
 * Usage: php database/create_ai_config_tables.php
 */
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/app/Helpers/helpers.php';
require_once ROOT_PATH . '/app/Core/Model.php';

$pdo = \App\Core\Model::getDb();
if (!$pdo) {
    echo "ERROR: Database connection failed. Please ensure MySQL is running in XAMPP.\n";
    exit(1);
}

echo "========================================================\n";
echo "   TERIMA JASA - AI CONFIGURATION TABLES MIGRATION\n";
echo "========================================================\n";

$sql = "
-- 1. Tabel Konfigurasi Provider AI
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

-- 2. Tabel Konfigurasi AI per Fitur Aplikasi
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

-- 3. Tabel Log Audit Pengujian Koneksi AI
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
";

try {
    $pdo->exec($sql);
    echo "[OK] Tables `ai_provider_configs`, `ai_feature_configs`, and `ai_connection_logs` created successfully.\n";

    // Seed default feature configs if not existing
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

    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM `ai_feature_configs` WHERE `feature_key` = ?");
    $insertStmt = $pdo->prepare("INSERT INTO `ai_feature_configs` (`feature_key`, `feature_label`, `is_enabled`) VALUES (?, ?, 1)");

    foreach ($features as $f) {
        $checkStmt->execute([$f[0]]);
        if ((int)$checkStmt->fetchColumn() === 0) {
            $insertStmt->execute([$f[0], $f[1]]);
            echo " - Seeded feature: {$f[0]}\n";
        }
    }

    echo "[OK] Initial seed completed successfully.\n";
} catch (Exception $e) {
    echo "ERROR during migration: " . $e->getMessage() . "\n";
    exit(1);
}
