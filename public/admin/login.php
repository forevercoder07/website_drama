<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Security.php';

// Admin tomonidan qaytarish
if (isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
    header('Location: /admin/index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!$username || !$password) {
        $error = 'Username va parol kerak';
    } else {
        try {
            $db = new Database();
            
            // Admin tekshirish
            $db->query("SELECT * FROM users WHERE username = ? AND is_admin = TRUE");
            $db->bind('s', $username);
            $admin = $db->single();
            
            if (!$admin) {
                $error = 'Admin topilmadi';
            } else {
                // Admin parolini static tekshirish (o'zgartirilsin production uchun!)
                $adminPasswordHash = password_hash('admin123', PASSWORD_BCRYPT);
                
                if (!password_verify($password, $adminPasswordHash) && $password !== 'admin123') {
                    // Hardcoded parol tekshiriniz
                    $error = 'Parol noto\'g\'ri';
                } else {
                    // Session yaratish
                    $_SESSION['user_id'] = $admin['id'];
                    $_SESSION['username'] = $admin['username'];
                    $_SESSION['is_admin'] = true;
                    $_SESSION['login_time'] = time();
                    
                    // Log qilish
                    $db->query("
                        INSERT INTO admin_logs 
                        (admin_id, action, target_type, target_id, ip_address)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $db->bind('i', $admin['id']);
                    $db->bind('s', 'admin_login');
                    $db->bind('s', 'admin');
                    $db->bind('i', $admin['id']);
                    $db->bind('s', $_SERVER['REMOTE_ADDR']);
                    
                    header('Location: /admin/index.php');
                    exit;
                }
            }
        } catch (Exception $e) {
            $error = 'Xato: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - DramaMini</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #ff4444;
            font-size: 32px;
            margin-bottom: 5px;
        }
        
        .logo p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #ff4444;
        }
        
        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            font-size: 14px;
            animation: slideDown 0.3s ease;
        }
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: #ff4444;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #cc0000;
        }
        
        .info {
            margin-top: 20px;
            padding: 15px;
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            border-radius: 5px;
            font-size: 13px;
            color: #0c5460;
        }
        
        .info h4 {
            margin-bottom: 8px;
            color: #003d82;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🎬 DramaMini</h1>
            <p>Admin Panel</p>
        </div>
        
        <?php if ($error) { ?>
        <div class="alert error">
            <?php echo Security::escape($error); ?>
        </div>
        <?php } ?>
        
        <?php if ($success) { ?>
        <div class="alert success">
            <?php echo Security::escape($success); ?>
        </div>
        <?php } ?>
        
        <form method="POST">
            <div class="form-group">
                <label>👤 Admin Username</label>
                <input type="text" name="username" placeholder="admin" required autofocus>
            </div>
            
            <div class="form-group">
                <label>🔑 Parol</label>
                <input type="password" name="password" placeholder="•••••••" required>
            </div>
            
            <button type="submit" class="btn">Kirish</button>
        </form>
        
        <div class="info">
            <h4>📋 Test Ma'lumotlari:</h4>
            <div>
                <strong>Username:</strong> admin<br>
                <strong>Password:</strong> admin123
            </div>
        </div>
        
        <div class="back-link">
            <a href="/">← Bosh sahifaga qaytish</a>
        </div>
    </div>
</body>
</html>
