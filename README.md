# Visitor Management System - Setup Guide

## Prerequisites
- PHP 8.1+ with mysqli and gd extensions
- MySQL/MariaDB 10.4+
- Composer 2.x
- Web server (Apache/Nginx) or PHP built-in server

## 1) Install PHP Dependencies
```bash
cd "/Users/sowmithgachula/Downloads/visitor_management_system-main 2"
composer install
```

## 2) Database Setup
- Create database (default `vms_db`):
```sql
CREATE DATABASE IF NOT EXISTS vms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vms_db;
```

- Core tables:
```sql
CREATE TABLE IF NOT EXISTS tbl_admin (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_name VARCHAR(100) NOT NULL,
  emailid VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS tbl_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_name VARCHAR(100) NOT NULL,
  emailid VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS tbl_events (
  event_id INT AUTO_INCREMENT PRIMARY KEY,
  event_name VARCHAR(150) NOT NULL,
  event_date DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tbl_visitors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(150) NULL,
  phone VARCHAR(30) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_visitors_event FOREIGN KEY (event_id)
    REFERENCES tbl_events(event_id) ON DELETE CASCADE
);
```

- Optional table used by `create_event_table.php`:
```sql
CREATE TABLE IF NOT EXISTS event_registrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  event VARCHAR(100) NOT NULL,
  event_date DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

- Seed admin user (MD5 to match existing code):
```sql
INSERT INTO tbl_admin (user_name, emailid, password)
VALUES ('Administrator', 'admin@example.com', MD5('admin123'))
ON DUPLICATE KEY UPDATE user_name=VALUES(user_name);
```

## 3) Configure DB Connection
Edit `connection.php` and set credentials:
```
$server="localhost";
$username="root";
$password="";
$databasename="vms_db";
```

## 4) Run the App
- Using PHP built-in server:
```bash
php -S localhost:8000
```
- Open `http://localhost:8000/index.php`
- Login:
  - Email: `admin@example.com`
  - Password: `admin123`
  - Choose Admin role

## Notes
- Redirects now use `index.php`; image paths use forward slashes.
- Keep the `vendor/` directory deployed for PDF export (dompdf).

