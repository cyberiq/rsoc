-- -------------------------------------------------------------------------
-- مخطط قاعدة بيانات تطبيق كرين العراقي لإنقاذ وسحب العجلات (MySQL / MariaDB)
-- متوافق بالكامل مع نظام الترميز العالمي utf8mb4 لدعم الأسماء العربية بطلاقة
-- -------------------------------------------------------------------------

CREATE DATABASE IF NOT EXISTS kreen_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE kreen_db;

-- تعطيل فحص المفاتيح الخارجية مؤقتاً لتهيئة نظيفة وتجنب أخطاء الحذف والتعديل
SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------------------
-- 1. جدول المستخدمين العاديين (العملاء والمدراء)
-- -------------------------------------------------------------------------
DROP TABLE IF EXISTS customers;
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    province VARCHAR(50) NOT NULL,
    email VARCHAR(191) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    verification_code VARCHAR(10) DEFAULT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    role VARCHAR(20) DEFAULT 'customer', -- customer, admin
    profile_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_customer_email (email),
    INDEX idx_customer_province (province)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- 2. جدول سائقي الكرين (الونش)
-- -------------------------------------------------------------------------
DROP TABLE IF EXISTS drivers;
CREATE TABLE drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    province VARCHAR(50) NOT NULL,
    email VARCHAR(191) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    wheel_number VARCHAR(50) DEFAULT NULL,
    wheel_type VARCHAR(100) DEFAULT NULL,
    wheel_color VARCHAR(50) DEFAULT NULL,
    wheel_model VARCHAR(20) DEFAULT NULL,
    verification_code VARCHAR(10) DEFAULT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    profile_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_driver_email (email),
    INDEX idx_driver_province (province)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- 3. جدول سائقي الكيا حمل
-- -------------------------------------------------------------------------
DROP TABLE IF EXISTS kias;
CREATE TABLE kias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    province VARCHAR(50) NOT NULL,
    email VARCHAR(191) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    car_number VARCHAR(50) DEFAULT NULL,
    car_model VARCHAR(100) DEFAULT NULL,
    verification_code VARCHAR(10) DEFAULT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    profile_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_kia_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- 4. جدول مواقع السائقين الفورية (Real-time GPS Tracking)
-- -------------------------------------------------------------------------
DROP TABLE IF EXISTS driver_locations;
CREATE TABLE driver_locations (
    driver_id INT PRIMARY KEY,
    latitude DOUBLE NOT NULL,
    longitude DOUBLE NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
    INDEX idx_coordinates (latitude, longitude) -- لتسريع حسابات معادلة هافرسين الجغرافية
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- 5. جدول طلبات السحب والإنقاذ (Service Requests)
-- -------------------------------------------------------------------------
DROP TABLE IF EXISTS service_requests;
CREATE TABLE service_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    driver_id INT DEFAULT NULL,
    latitude DOUBLE NOT NULL,
    longitude DOUBLE NOT NULL,
    status VARCHAR(50) DEFAULT 'pending', -- pending, accepted, completed, cancelled
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL,
    INDEX idx_request_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- 6. جدول توكنات استعادة كلمات المرور (Password Resets)
-- -------------------------------------------------------------------------
DROP TABLE IF EXISTS password_resets;
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(191) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    INDEX idx_reset_email_token (email, token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إعادة تفعيل فحص المفاتيح الخارجية لضمان سلامة العلاقات والبيانات
SET FOREIGN_KEY_CHECKS = 1;
