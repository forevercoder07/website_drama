<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Security.php';

// Allaqachon login qilgan
if (isset($_SESSION['user_id'])) {
    header('Location: /');
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
            $auth = new Auth($db);
            
            $result = $auth->login($username, $password);
            
            if ($result['success']) {
                $success = 'Muvaffaqiyatli login!';
                header('Location: / ', true, 303);
                exit;
            } elseif ($result['requires_device_approval'] ?? false) {
                $error = '⚠️ Bu qurilma hali tasdiqlanmagan. Admin ruxsat bergunini kuting.';
            } else {
                $error = $result['message'] ?? 'Login xatosi';
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
    <title>Kirish - DramaMini Yopiq Bo'lim</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: rgba(26, 31, 58, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid #ff4444;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(255,68,68,0.3);
            width: 100%;
            max-width: 420px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            background: linear-gradient(135deg, #ff4444, #ff8800);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 36px;
            margin-bottom: 5px;
        }
        
        .logo p {
            color: #aaa;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #fff;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            background: rgba(255,255,255,0.05);
            border: 2px solid #2a2f4a;
            border-radius: 5px;
            color: #fff;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input::placeholder {
            color: #666;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #ff4444;
            background: rgba(255,68,68,0.1);
        }
        
        .alert {
            padding: 14px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: left;
            font-size: 14px;
            animation: slideDown 0.3s ease;
        }
        
        .alert.error {
            background: rgba(244, 67, 54, 0.2);
            color: #ff6b6b;
            border-left: 4px solid #f44336;
        }
        
        .alert.success {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
            border-left: 4px solid #4caf50;
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
            background: linear-gradient(135deg, #ff4444, #ff2200);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 15px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255,68,68,0.4);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .info {
            margin-top: 25px;
            padding: 15px;
            background: rgba(33, 150, 243, 0.1);
            border-left: 4px solid #2196F3;
            border-radius: 5px;
            font-size: 13px;
            color: #87ceeb;
        }
        
        .info h4 {
            margin-bottom: 8px;
            color: #64b5f6;
        }
        
        .info p {
            margin-bottom: 5px;
        }
        
        .features {
            margin-top: 25px;
            padding: 15px;
            background: rgba(255,68,68,0.1);
            border-radius: 5px;
            font-size: 13px;
            color: #ff8888;
        }
        
        .features h4 {
            margin-bottom: 10px;
            color: #ff4444;
        }
        
        .features ul {
            list-style: none;
            padding-left: 0;
        }
        
        .features li {
            padding: 5px 0;
            padding-left: 20px;
            position: relative;
        }
        
        .features li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #ff4444;
            font-weight: bold;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #ff4444;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }
        
        .back-link a:hover {
            color: #ff8800;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🎬 DramaMini</h1>
            <p>🔒 Yopiq Bo'lim</p>
        </div>
        
        <?php if ($error) { ?>
        <div class="alert error">
            <?php echo Security::escape($error); ?>
        </div>
        <?php } ?>
        
        <?php if ($success) { ?>
        <div class="alert success">
            ✓ <?php echo Security::escape($success); ?>
        </div>
        <?php } ?>
        
        <form method="POST">
            <div class="form-group">
                <label>👤 Username</label>
                <input type="text" name="username" placeholder="Username kiriting" required autofocus>
            </div>
            
            <div class="form-group">
                <label>🔑 Vaqtga Berilgan Parol</label>
                <input type="password" name="password" placeholder="Admin tomonidan berilgan parol" required>
            </div>
            
            <button type="submit" class="btn">Kirish</button>
        </form>
        
        <div class="features">
            <h4>🛡️ Xavfsizlik</h4>
            <ul>
                <li>Device Fingerprinting</li>
                <li>Vaqtga Cheklangan Parol</li>
                <li>Admin Tasdiq Tizimi</li>
                <li>Login History Kuzatish</li>
            </ul>
        </div>
        
        <div class="info">
            <h4>📋 Diqqat!</h4>
            <p>🔐 Yopiq bo'limga kirish uchun admin tomonidan belgilangan parol kerak.</p>
            <p>📱 Yangi qurilmadan kirsangiz, admin ruxsat bergunini kuting.</p>
        </div>
        
        <div class="back-link">
            <a href="/">← Bosh sahifaga qaytish</a>
        </div>
    </div>
</body>
</html>
