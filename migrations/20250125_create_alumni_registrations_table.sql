-- Migration: Create alumni_registrations table
-- Date: 2025-01-25
-- Description: Creates the alumni_registrations table for storing alumni self-registration data

CREATE TABLE IF NOT EXISTS alumni_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    roll_number VARCHAR(50) NOT NULL,
    passed_out_batch VARCHAR(50) NOT NULL,
    department VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    current_organization VARCHAR(255),
    current_designation VARCHAR(255),
    call_number VARCHAR(20) NOT NULL,
    whatsapp_number VARCHAR(20),
    message LONGTEXT,
    status ENUM('Pending', 'Verified', 'Archived') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_email (email),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;