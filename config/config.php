<?php
// ==================== CONFIG ====================
// DramaMini Configuration

// Database Settings
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'dramamini');

// Security Settings
define('JWT_SECRET', 'your-secret-key-change-this-in-production-12345678901234567890');
define('SESSION_TIMEOUT', 3600); // 1 soat
define('PASSWORD_EXPIRE_DAYS', 30); // Parol 30 kun yaqinda to'liqlanadi
define('RATE_LIMIT_ATTEMPTS', 5); // Login uchun 5 urinish
define('RATE_LIMIT_WINDOW', 900); // 15 minut
define('MAX_DEVICES_PER_USER', 3); // Bitta foydalanuvchi maksimum 3 ta qurilmadan

// Admin Settings
define('ADMIN_PANEL_URL', '/admin/');
define('ADMIN_SESSION_TIMEOUT', 1800); // Admin 30 min timeout

// API Settings
define('API_URL', '/api/');
define('API_VERSION', 'v1');

// File Upload Settings
define('UPLOAD_DIR', '/uploads/');
define('MAX_FILE_SIZE', 104857600); // 100MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mkv', 'webm']);

// Email Settings (optional)
define('MAIL_FROM', 'noreply@dramamini.net');
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);

// Timezone
date_default_timezone_set('Asia/Tashkent');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Production: off
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Security Headers
define('CORS_ALLOWED_ORIGINS', ['http://localhost:3000', 'https://dramamini.net']);
define('CSRF_TOKEN_LIFETIME', 3600);

// Logging
define('LOG_FILE', __DIR__ . '/logs/app.log');
define('LOG_ADMIN_ACTIONS', true);

// Device Fingerprinting
define('DEVICE_FINGERPRINT_ALGORITHM', 'sha256');
?>
