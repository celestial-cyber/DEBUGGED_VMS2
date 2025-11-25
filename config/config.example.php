<?php
/**
 * Configuration File Example
 * 
 * Copy this file to config.php and update with your database credentials
 * DO NOT commit config.php to version control
 */

return [
    'database' => [
        'host' => 'localhost',
        'username' => 'root',
        'password' => 'your_password_here',
        'database' => 'vms_db',
        'charset' => 'utf8mb4'
    ],
    'session' => [
        'lifetime' => 7200, // 2 hours
        'name' => 'VMS_SESSION'
    ],
    'app' => [
        'name' => 'Visitor Management System',
        'version' => '1.0.0',
        'timezone' => 'Asia/Kolkata'
    ]
];

