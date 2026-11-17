-- ============================================================
-- DATABASE SCHEME: Terima Jasa
-- Platform Penghubung Customer & Teknisi dengan AI Assistant
-- DBMS: MySQL / MariaDB (InnoDB Engine, utf8mb4)
-- ============================================================

CREATE DATABASE IF NOT EXISTS `terima_jasa_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `terima_jasa_db`;

-- Matikan foreign key checks sementara untuk clean migration
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `otp_verifications`;
DROP TABLE IF EXISTS `ai_chats`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `services`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `technician_profiles`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- 1. TABEL: users
-- ------------------------------------------------------------
CREATE TABLE `users` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'customer', 'teknisi') NOT NULL DEFAULT 'customer',
    `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    `phone` VARCHAR(25) NULL,
    `remember_token` VARCHAR(100) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_status` (`status`),
    INDEX `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. TABEL: technician_profiles (User 1 : 1 TechnicianProfile)
-- ------------------------------------------------------------
CREATE TABLE `technician_profiles` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
    `expertise` VARCHAR(255) NOT NULL,
    `experience` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(25) NOT NULL,
    `address` TEXT NULL,
    `location` VARCHAR(150) NOT NULL,
    `availability` ENUM('available', 'busy', 'offline') NOT NULL DEFAULT 'available',
    `verification_status` ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_technician_profiles_user` 
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_tech_location` (`location`),
    INDEX `idx_tech_availability` (`availability`),
    INDEX `idx_tech_verification_status` (`verification_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. TABEL: categories (Category 1 : N Services)
-- ------------------------------------------------------------
CREATE TABLE `categories` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_categories_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. TABEL: services (Service 1 : N Orders)
-- ------------------------------------------------------------
CREATE TABLE `services` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `estimated_price` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_services_category` 
        FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX `idx_services_category_id` (`category_id`),
    INDEX `idx_services_status` (`status`),
    INDEX `idx_services_price` (`estimated_price`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. TABEL: orders (Customer 1 : N Orders, Tech 1 : N Orders)
-- ------------------------------------------------------------
CREATE TABLE `orders` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `technician_id` BIGINT UNSIGNED NULL,
    `service_id` BIGINT UNSIGNED NOT NULL,
    `description` TEXT NOT NULL,
    `location` VARCHAR(255) NOT NULL,
    `schedule` DATETIME NOT NULL,
    `problem_image` VARCHAR(255) NULL,
    `status` ENUM('pending', 'accepted', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_orders_customer` 
        FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_orders_technician` 
        FOREIGN KEY (`technician_id`) REFERENCES `users` (`id`) 
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_orders_service` 
        FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX `idx_orders_customer_id` (`customer_id`),
    INDEX `idx_orders_technician_id` (`technician_id`),
    INDEX `idx_orders_service_id` (`service_id`),
    INDEX `idx_orders_status` (`status`),
    INDEX `idx_orders_schedule` (`schedule`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. TABEL: reviews (Order 1 : 1 Review)
-- ------------------------------------------------------------
CREATE TABLE `reviews` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` BIGINT UNSIGNED NOT NULL UNIQUE,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `technician_id` BIGINT UNSIGNED NOT NULL,
    `rating` TINYINT UNSIGNED NOT NULL,
    `comment` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_reviews_order` 
        FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_reviews_customer` 
        FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_reviews_technician` 
        FOREIGN KEY (`technician_id`) REFERENCES `users` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_reviews_customer_id` (`customer_id`),
    INDEX `idx_reviews_technician_id` (`technician_id`),
    INDEX `idx_reviews_rating` (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7. TABEL: ai_chats (Customer 1 : N AI Chats)
-- ------------------------------------------------------------
CREATE TABLE `ai_chats` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `question` TEXT NOT NULL,
    `answer` TEXT NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ai_chats_customer` 
        FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_ai_chats_customer_id` (`customer_id`),
    INDEX `idx_ai_chats_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 8. TABEL: otp_verifications (WhatsApp OTP Authentication & Security)
-- ------------------------------------------------------------
CREATE TABLE `otp_verifications` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NULL,
    `phone` VARCHAR(30) NOT NULL,
    `otp_hash` VARCHAR(255) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `attempts` INT NOT NULL DEFAULT 0,
    `max_attempts` INT NOT NULL DEFAULT 5,
    `resend_available_at` DATETIME NOT NULL,
    `consumed_at` DATETIME NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_otp_verifications_user` 
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_otp_phone` (`phone`),
    INDEX `idx_otp_expires` (`expires_at`),
    INDEX `idx_otp_consumed` (`consumed_at`),
    INDEX `idx_otp_phone_consumed` (`phone`, `consumed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 9. TABEL: ai_provider_configs (Manajemen Model & Provider AI)
-- ------------------------------------------------------------
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

-- ------------------------------------------------------------
-- 10. TABEL: ai_feature_configs (Pengaturan Model per Fitur AI)
-- ------------------------------------------------------------
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

-- ------------------------------------------------------------
-- 11. TABEL: ai_connection_logs (Log & Audit Pengujian Koneksi AI)
-- ------------------------------------------------------------
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

-- ------------------------------------------------------------
-- 12. TABEL: notifications (Sistem Notifikasi Multi-Role Terintegrasi)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `related_type` VARCHAR(50) NULL,
    `related_id` BIGINT UNSIGNED NULL,
    `read_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_notifications_user` 
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_notif_user_id` (`user_id`),
    INDEX `idx_notif_read_at` (`read_at`),
    INDEX `idx_notif_user_unread` (`user_id`, `read_at`),
    INDEX `idx_notif_related` (`related_type`, `related_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


