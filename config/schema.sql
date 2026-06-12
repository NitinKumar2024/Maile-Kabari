-- Disable foreign key checks temporarily to drop tables in any order
SET FOREIGN_KEY_CHECKS = 0;

-- Create Database if not exists
CREATE DATABASE IF NOT EXISTS `rohit_kabari` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `rohit_kabari`;

-- Drop existing tables and views if they exist to prevent conflicts
DROP VIEW IF EXISTS `v_order_summary`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `scrap_categories`;
DROP TABLE IF EXISTS `user_tokens`;
DROP TABLE IF EXISTS `user_remember_tokens`;
DROP TABLE IF EXISTS `user_profiles`;
DROP TABLE IF EXISTS `scrap_rates`;
DROP TABLE IF EXISTS `quantity_units`;
DROP TABLE IF EXISTS `order_status_history`;
DROP TABLE IF EXISTS `order_images`;
DROP TABLE IF EXISTS `order_field_notes`;
DROP TABLE IF EXISTS `order_daily_sequence`;
DROP TABLE IF EXISTS `login_attempts`;
DROP TABLE IF EXISTS `app_settings`;
DROP TABLE IF EXISTS `users`;

-- 1. Users Table
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(15) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `role` ENUM('customer', 'staff', 'admin') DEFAULT 'customer',
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_phone (`phone`),
  INDEX idx_role (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. User Tokens Table (For persistent secure auto-login)
CREATE TABLE `user_tokens` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `selector` VARCHAR(16) NOT NULL UNIQUE,
  `validator_hash` VARCHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX idx_selector (`selector`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Scrap Categories Table
CREATE TABLE `scrap_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `unit` VARCHAR(20) DEFAULT 'kg',
  `rate_per_unit` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `icon` VARCHAR(100) DEFAULT 'default-scrap',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Orders Table
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `pickup_address` TEXT NOT NULL,
  `lat` DECIMAL(10, 8) DEFAULT NULL,
  `lng` DECIMAL(11, 8) DEFAULT NULL,
  `pickup_date` DATE NOT NULL,
  `pickup_time_slot` VARCHAR(50) NOT NULL,
  `status` ENUM('pending', 'assigned', 'collected', 'cancelled') DEFAULT 'pending',
  `assigned_staff_id` INT DEFAULT NULL,
  `total_estimated_price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `total_actual_price` DECIMAL(10, 2) DEFAULT NULL,
  `customer_notes` TEXT DEFAULT NULL,
  `admin_notes` TEXT DEFAULT NULL,
  `staff_notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`assigned_staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  INDEX idx_status (`status`),
  INDEX idx_pickup_date (`pickup_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Order Items Table
CREATE TABLE `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `category_id` INT NOT NULL,
  `estimated_quantity` DECIMAL(10, 2) NOT NULL,
  `actual_quantity` DECIMAL(10, 2) DEFAULT NULL,
  `rate_at_order` DECIMAL(10, 2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `scrap_categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Notifications Table
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX idx_user_unread (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed initial scrap categories
INSERT INTO `scrap_categories` (`name`, `slug`, `unit`, `rate_per_unit`, `icon`) VALUES
('Iron', 'iron', 'kg', 28.00, 'iron'),
('Plastic', 'plastic', 'kg', 14.00, 'plastic'),
('Cardboard', 'cardboard', 'kg', 12.00, 'cardboard'),
('Books & Papers', 'books', 'kg', 15.00, 'books'),
('Aluminium', 'aluminium', 'kg', 110.00, 'aluminium'),
('Copper', 'copper', 'kg', 450.00, 'copper'),
('Zinc', 'zinc', 'kg', 140.00, 'zinc')
ON DUPLICATE KEY UPDATE `rate_per_unit` = VALUES(`rate_per_unit`), `unit` = VALUES(`unit`);

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;
