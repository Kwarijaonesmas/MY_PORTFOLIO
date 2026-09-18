-- Database Schema for Bushenyi Hostel Booking & Management System
-- Target Database: mydb (Mobile Money Simulator Enabled)

CREATE DATABASE IF NOT EXISTS `mydb` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mydb`;

-- 1. Table structure for users
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('student', 'owner', 'admin') NOT NULL DEFAULT 'student',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Table structure for hostels
CREATE TABLE IF NOT EXISTS `hostels` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `owner_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `location` VARCHAR(100) NOT NULL,
  `price` INT NOT NULL,
  `type` ENUM('Single', 'Double', 'Self-contained') NOT NULL,
  `facilities` TEXT NOT NULL,
  `photo` VARCHAR(255) DEFAULT 'default_hostel.jpg',
  `status` ENUM('pending', 'approved') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Table structure for rooms
CREATE TABLE IF NOT EXISTS `rooms` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `hostel_id` INT NOT NULL,
  `room_no` VARCHAR(50) NOT NULL,
  `status` ENUM('available', 'booked') DEFAULT 'available',
  FOREIGN KEY (`hostel_id`) REFERENCES `hostels`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Table structure for simulated mobile money accounts
CREATE TABLE IF NOT EXISTS `mobile_money_accounts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `network` ENUM('MTN', 'Airtel') NOT NULL,
  `phone_number` VARCHAR(20) NOT NULL UNIQUE,
  `account_name` VARCHAR(100) NOT NULL,
  `simulated_balance` INT NOT NULL DEFAULT 500000,
  `test_pin_hash` VARCHAR(255) NOT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Table structure for bookings
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `room_id` INT NOT NULL,
  `hostel_id` INT NOT NULL,
  `booking_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `payment_status` ENUM('PENDING', 'PROCESSING', 'SUCCESSFUL', 'FAILED', 'INSUFFICIENT_BALANCE', 'INVALID_PIN', 'CANCELLED') DEFAULT 'PENDING',
  `payment_method` VARCHAR(50) DEFAULT 'MTN Mobile Money Demo',
  `payment_phone` VARCHAR(20) DEFAULT NULL,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`hostel_id`) REFERENCES `hostels`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Table structure for simulated payment transactions log
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `account_id` INT DEFAULT NULL,
  `network` VARCHAR(50) NOT NULL,
  `phone_number` VARCHAR(20) NOT NULL,
  `amount` INT NOT NULL,
  `transaction_ref` VARCHAR(50) NOT NULL UNIQUE,
  `status` ENUM('PENDING', 'PROCESSING', 'SUCCESSFUL', 'FAILED', 'INSUFFICIENT_BALANCE', 'INVALID_PIN', 'CANCELLED') NOT NULL,
  `message` TEXT,
  `remaining_balance` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Table structure for reviews
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `hostel_id` INT NOT NULL,
  `rating` INT NOT NULL DEFAULT 5,
  `comment` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`hostel_id`) REFERENCES `hostels`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin (Password: admin123)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`) VALUES
(1, 'System Administrator', 'admin@gmail.com', '$2y$10$d6.P24fAN2SPejuEODwdH.VhdPM7e9mr3t/kTJ85T8/jiNLjLObcK', 'admin')
ON DUPLICATE KEY UPDATE `password` = '$2y$10$d6.P24fAN2SPejuEODwdH.VhdPM7e9mr3t/kTJ85T8/jiNLjLObcK', `role` = 'admin';
