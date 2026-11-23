-- database.sql - Skema database MySQL Jepret.in Rental Kamera
-- MySQL 8.x / MariaDB
--
-- Catatan: aplikasi juga bisa membuat tabel ini otomatis.
-- File ini untuk yang ingin menyiapkan MySQL secara manual (XAMPP / Laragon / CLI).
-- Data 10 kamera akan di-seed otomatis oleh aplikasi saat tabel cameras masih kosong.

CREATE DATABASE IF NOT EXISTS `jepret_in` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `jepret_in`;

-- 1. Produk kamera
CREATE TABLE IF NOT EXISTS `cameras` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(191) NOT NULL UNIQUE,
    `name` VARCHAR(191) NOT NULL,
    `brand` VARCHAR(80) NOT NULL,
    `category` VARCHAR(60) NOT NULL,
    `tag` VARCHAR(120) NOT NULL,
    `daily_price` INT NOT NULL,
    `image` VARCHAR(255) NOT NULL,
    `sensor` VARCHAR(200) NOT NULL,
    `video` VARCHAR(200) NOT NULL,
    `iso` VARCHAR(160) NOT NULL,
    `mount` VARCHAR(160) NOT NULL,
    `weight` VARCHAR(60) NOT NULL,
    `stabilization` VARCHAR(200) NOT NULL,
    `description` TEXT NOT NULL,
    `includes` TEXT NOT NULL,
    `is_featured` INT DEFAULT 0,
    `is_available` INT DEFAULT 1,
    `stock` INT NOT NULL DEFAULT 1,
    `created_at` VARCHAR(19) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Akun pengguna (login customer)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(191) NOT NULL,
    `email` VARCHAR(191) NOT NULL UNIQUE,
    `phone` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at` VARCHAR(19) DEFAULT NULL,
    `updated_at` VARCHAR(19) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Pelanggan (dibuat otomatis dari nomor WhatsApp saat booking)
CREATE TABLE IF NOT EXISTS `customers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(191) NOT NULL,
    `phone` VARCHAR(50) NOT NULL UNIQUE,
    `instagram` VARCHAR(191) DEFAULT '',
    `created_at` VARCHAR(19) DEFAULT NULL,
    `updated_at` VARCHAR(19) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Pemesanan
CREATE TABLE IF NOT EXISTS `bookings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_code` VARCHAR(60) NOT NULL UNIQUE,
    `user_id` INT DEFAULT NULL,
    `customer_id` INT DEFAULT NULL,
    `camera_id` INT DEFAULT NULL,
    `customer_name` VARCHAR(191) NOT NULL,
    `customer_phone` VARCHAR(50) NOT NULL,
    `start_date` VARCHAR(10) NOT NULL,
    `end_date` VARCHAR(10) NOT NULL,
    `total_days` INT NOT NULL DEFAULT 1,
    `addon_keys` TEXT DEFAULT NULL,
    `addon_labels` TEXT DEFAULT NULL,
    `addon_per_day` INT NOT NULL DEFAULT 0,
    `camera_subtotal` INT NOT NULL DEFAULT 0,
    `addon_subtotal` INT NOT NULL DEFAULT 0,
    `discount` INT NOT NULL DEFAULT 0,
    `total_price` INT NOT NULL DEFAULT 0,
    `pickup_method` VARCHAR(191) DEFAULT '',
    `payment_method` VARCHAR(20) DEFAULT 'cash',
    `note` TEXT DEFAULT NULL,
    `status` VARCHAR(20) DEFAULT 'pending',
    `created_at` VARCHAR(19) DEFAULT NULL,
    `updated_at` VARCHAR(19) DEFAULT NULL,
    KEY `idx_bookings_customer` (`customer_id`),
    KEY `idx_bookings_camera` (`camera_id`),
    KEY `idx_bookings_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Pembayaran yang benar-benar diterima (cash / QRIS)
CREATE TABLE IF NOT EXISTS `testimonials` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `booking_id` INT NOT NULL UNIQUE,
    `name` VARCHAR(191) NOT NULL,
    `rating` INT NOT NULL DEFAULT 5,
    `message` TEXT NOT NULL,
    `role` VARCHAR(191) NOT NULL DEFAULT '',
    `created_at` VARCHAR(19) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_id` INT NOT NULL,
    `method` VARCHAR(20) NOT NULL DEFAULT 'cash',
    `amount` INT NOT NULL DEFAULT 0,
    `note` VARCHAR(255) DEFAULT '',
    `paid_at` VARCHAR(19) DEFAULT NULL,
    `created_at` VARCHAR(19) DEFAULT NULL,
    KEY `idx_payments_booking` (`booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Token reset password (hash, sekali pakai)
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `token_hash` VARCHAR(64) NOT NULL UNIQUE,
    `expires_at` INT NOT NULL,
    `used_at` INT DEFAULT NULL,
    `ip` VARCHAR(45) DEFAULT '',
    `created_at` VARCHAR(19) DEFAULT NULL,
    KEY `idx_password_resets_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Catatan percobaan login untuk rate limit per IP dan per akun
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ip` VARCHAR(45) NOT NULL,
    `identifier` VARCHAR(191) NOT NULL,
    `attempted_at` INT NOT NULL,
    KEY `idx_login_attempts_window` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
