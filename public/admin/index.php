<?php
// ==================== ADMIN PANEL ====================

session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Security.php';
require_once __DIR__ . '/../includes/Admin.php';
require_once __DIR__ . '/../includes/Movie.php';

// Admin check
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] !== true) {
    header('Location: /admin/login.php');
    exit;
}

$db = new Database();
$admin = new Admin($db, $_SESSION['user_id']);

// ==================== ACTIONS ====================

// Yangi user yaratish
if ($_POST['action'] === 'create_user') {
    $result = $admin->createUser($_POST['username'], $_POST['email']);
    echo json_encode($result);
    exit;
}

// Vaqtga berilgan parol belgilash
if ($_POST['action'] === 'set_temp_password') {
    $userId = $_POST['user_id'];
    $expiresInDays = $_POST['expires_in_days'] ?? 30;
    $result = $admin->setTemporaryPassword($userId, $expiresInDays);
    echo json_encode($result);
    exit;
}

// Parol bekor qilish
if ($_POST['action'] === 'revoke_password') {
    $passwordId = $_POST['password_id'];
    $result = $admin->revokeTemporaryPassword($passwordId);
    echo json_encode($result);
    exit;
}

// Qurilma tasdiq qilish
if ($_POST['action'] === 'approve_device') {
    $deviceId = $_POST['device_id'];
    $result = $admin->approveDevice($deviceId);
    echo json_encode($result);
    exit;
}

// Qurilma rad qilish
if ($_POST['action'] === 'reject_device') {
    $deviceId = $_POST['device_id'];
    $result = $admin->rejectDevice($deviceId);
    echo json_encode($result);
    exit;
}

// Foydalanuvchini bloklash
if ($_POST['action'] === 'block_user') {
    $userId = $_POST['user_id'];
    $result = $admin->blockUser($userId);
    echo json_encode($result);
    exit;
}

// Foydalanuvchini qo'lga olish
if ($_POST['action'] === 'unblock_user') {
    $userId = $_POST['user_id'];
    $result = $admin->unblockUser($userId);
    echo json_encode($result);
    exit;
}

// Yangi public kino
if ($_POST['action'] === 'create_movie') {
    $movie = new Movie($db);
    $result = $movie->createPublicMovie([
        'title' => $_POST['title'],
        'description' => $_POST['description'],
        'thumbnail' => $_POST['thumbnail'],
        'poster' => $_POST['poster'],
        'category_id' => $_POST['category_id'],
        'duration' => $_POST['duration'],
        'rating' => $_POST['rating'],
        'release_date' => $_POST['release_date']
    ]);
    echo json_encode($result);
    exit;
}

// Yangi private kino
if ($_POST['action'] === 'create_private_movie') {
    $movie = new Movie($db);
    $result = $movie->createPrivateMovie([
        'title' => $_POST['title'],
        'description' => $_POST['description'],
        'thumbnail' => $_POST['thumbnail'],
        'poster' => $_POST['poster'],
        'category_id' => $_POST['category_id'],
        'duration' => $_POST['duration'],
        'rating' => $_POST['rating'],
        'release_date' => $_POST['release_date']
    ]);
    echo json_encode($result);
    exit;
}

// Kino yangilash
if ($_POST['action'] === 'update_movie') {
    $movie = new Movie($db);
    $result = $movie->updatePublicMovie($_POST['movie_id'], [
        'title' => $_POST['title'],
        'description' => $_POST['description'],
        'thumbnail' => $_POST['thumbnail'],
        'poster' => $_POST['poster'],
        'category_id' => $_POST['category_id'],
        'duration' => $_POST['duration'],
        'rating' => $_POST['rating'],
        'release_date' => $_POST['release_date']
    ]);
    echo json_encode($result);
    exit;
}

// Kino o'chirish
if ($_POST['action'] === 'delete_movie') {
    $movie = new Movie($db);
    $result = $movie->deletePublicMovie($_POST['movie_id']);
    echo json_encode($result);
    exit;
}

// ==================== PAGES ====================

$page = $_GET['page'] ?? 'dashboard';

?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - DramaMini</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background: #1a1a1a;
            color: white;
            padding: 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar h2 {
            margin-bottom: 30px;
            border-bottom: 2px solid #ff4444;
            padding-bottom: 10px;
        }
        
        .sidebar a {
            display: block;
            color: #ddd;
            text-decoration: none;
            padding: 12px 15px;
            margin: 5px 0;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .sidebar a:hover,
        .sidebar a.active {
            background: #ff4444;
            color: white;
        }
        
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 30px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 28px;
        }
        
        .logout-btn {
            background: #ff4444;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #ff4444;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .btn {
            background: #ff4444;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #cc0000;
        }
        
        .btn.secondary {
            background: #666;
        }
        
        .btn.secondary:hover {
            background: #444;
        }
        
        table {
            width: 100%;
            background: white;
            border-collapse: collapse;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        table thead {
            background: #1a1a1a;
            color: white;
        }
        
        table th,
        table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        table tbody tr:hover {
            background: #f9f9f9;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge.active {
            background: #4caf50;
            color: white;
        }
        
        .badge.blocked {
            background: #f44336;
            color: white;
        }
        
        .badge.pending {
            background: #ff9800;
            color: white;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- SIDEBAR -->
        <div class="sidebar">
            <h2>📊 Admin Panel</h2>
            
            <a href="?page=dashboard" <?php echo $page === 'dashboard' ? 'class="active"' : ''; ?>>
                📈 Dashboard
            </a>
            <a href="?page=users" <?php echo $page === 'users' ? 'class="active"' : ''; ?>>
                👥 Foydalanuvchilar
            </a>
            <a href="?page=passwords" <?php echo $page === 'passwords' ? 'class="active"' : ''; ?>>
                🔑 Parollar
            </a>
            <a href="?page=devices" <?php echo $page === 'devices' ? 'class="active"' : ''; ?>>
                📱 Qurilmalar
            </a>
            <a href="?page=public-movies" <?php echo $page === 'public-movies' ? 'class="active"' : ''; ?>>
                🎬 Public Kinolar
            </a>
            <a href="?page=private-movies" <?php echo $page === 'private-movies' ? 'class="active"' : ''; ?>>
                🔒 Yopiq Kinolar
            </a>
            <a href="?page=logs" <?php echo $page === 'logs' ? 'class="active"' : ''; ?>>
                📋 Loglar
            </a>
            
            <hr style="margin: 20px 0; border: none; border-top: 1px solid #444;">
            
            <a href="/api/logout.php" style="color: #ff4444;">
                🚪 Logout
            </a>
        </div>
        
        <!-- MAIN CONTENT -->
        <div class="main-content">
            <div class="header">
                <h1>
                    <?php
                    switch ($page) {
                        case 'users': echo '👥 Foydalanuvchilar'; break;
                        case 'passwords': echo '🔑 Vaqtga berilgan Parollar'; break;
                        case 'devices': echo '📱 Qurilmalar'; break;
                        case 'public-movies': echo '🎬 Public Kinolar'; break;
                        case 'private-movies': echo '🔒 Yopiq Kinolar'; break;
                        case 'logs': echo '📋 Admin Loglar'; break;
                        default: echo '📊 Dashboard';
                    }
                    ?>
                </h1>
                <button class="logout-btn" onclick="window.location.href='/api/logout.php'">
                    Logout
                </button>
            </div>
            
            <?php
            
            // ==================== DASHBOARD ====================
            if ($page === 'dashboard') {
                // Statistika
                $db->query("SELECT COUNT(*) as count FROM users WHERE is_admin = FALSE");
                $userCount = $db->single()['count'];
                
                $db->query("SELECT COUNT(*) as count FROM movies");
                $movieCount = $db->single()['count'];
                
                $db->query("SELECT COUNT(*) as count FROM private_movies");
                $privateMovieCount = $db->single()['count'];
                
                $db->query("SELECT COUNT(*) as count FROM device_fingerprints WHERE is_approved = FALSE");
                $pendingDevices = $db->single()['count'];
                
                $db->query("SELECT COUNT(*) as count FROM temp_passwords WHERE is_revoked = FALSE AND expires_at > NOW()");
                $activePasswords = $db->single()['count'];
                
                ?>
                <div class="stats">
                    <div class="stat-card">
                        <h3>👥 Jami Foydalanuvchilar</h3>
                        <div class="number"><?php echo $userCount; ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <h3>🎬 Public Kinolar</h3>
                        <div class="number"><?php echo $movieCount; ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <h3>🔒 Yopiq Kinolar</h3>
                        <div class="number"><?php echo $privateMovieCount; ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <h3>⏳ Tasdiq kutayotgan Qurilmalar</h3>
                        <div class="number"><?php echo $pendingDevices; ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <h3>🔑 Faol Parollar</h3>
                        <div class="number"><?php echo $activePasswords; ?></div>
                    </div>
                </div>
                
                <h2 style="margin-top: 40px; margin-bottom: 20px;">Yangi Foydalanuvchi Yaratish</h2>
                <div style="background: white; padding: 20px; border-radius: 8px; max-width: 500px;">
                    <form id="createUserForm">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" id="username" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" id="email" required>
                        </div>
                        <button type="button" class="btn" onclick="createUser()">Yaratish</button>
                    </form>
                </div>
                
            <?php
            }
            
            // ==================== USERS ====================
            elseif ($page === 'users') {
                $users = $admin->getAllUsers();
                ?>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Yaratildi</th>
                            <th>Amallar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users['users'] as $user) { ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo Security::escape($user['username']); ?></td>
                            <td><?php echo Security::escape($user['email']); ?></td>
                            <td>
                                <span class="badge <?php echo $user['status'] === 'active' ? 'active' : 'blocked'; ?>">
                                    <?php echo $user['status'] === 'active' ? '✓ Faol' : '✗ Bloklangan'; ?>
                                </span>
                            </td>
                            <td><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <button class="btn" style="padding: 5px 10px; font-size: 12px;" 
                                    onclick="showUserMonitoring(<?php echo $user['id']; ?>)">
                                    📊 Kuzatish
                                </button>
                                <button class="btn secondary" style="padding: 5px 10px; font-size: 12px;" 
                                    onclick="toggleUserStatus(<?php echo $user['id']; ?>, '<?php echo $user['status']; ?>')">
                                    <?php echo $user['status'] === 'active' ? '🔒 Bloklash' : '✓ Qo\'lga Olish'; ?>
                                </button>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
                
            <?php
            }
            
            // ==================== PASSWORDS ====================
            elseif ($page === 'passwords') {
                $users = $admin->getAllUsers();
                ?>
                
                <h3 style="margin-bottom: 20px;">Foydalanuvchiga Parol Belgilang</h3>
                <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                    <div class="form-group">
                        <label>Foydalanuvchi</label>
                        <select id="userSelect" required>
                            <option value="">-- Tanlang --</option>
                            <?php foreach ($users['users'] as $user) { ?>
                            <option value="<?php echo $user['id']; ?>">
                                <?php echo $user['username']; ?> (<?php echo $user['email']; ?>)
                            </option>
                            <?php } ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Parol Muddati (kun)</label>
                        <input type="number" id="expiresDays" value="30" min="1">
                    </div>
                    
                    <button class="btn" onclick="setTemporaryPassword()">Parol Yaratish</button>
                </div>
                
                <h3 style="margin-bottom: 20px;">Barcha Vaqtga Berilgan Parollar</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Foydalanuvchi</th>
                            <th>Yaratildi</th>
                            <th>Tugaydi</th>
                            <th>Status</th>
                            <th>Amallar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $db->query("
                            SELECT tp.*, u.username
                            FROM temp_passwords tp
                            JOIN users u ON tp.user_id = u.id
                            ORDER BY tp.created_at DESC
                            LIMIT 100
                        ");
                        foreach ($db->resultSet() as $pwd) {
                        ?>
                        <tr>
                            <td><?php echo $pwd['username']; ?></td>
                            <td><?php echo date('d.m.Y H:i', strtotime($pwd['created_at'])); ?></td>
                            <td><?php echo date('d.m.Y H:i', strtotime($pwd['expires_at'])); ?></td>
                            <td>
                                <?php
                                if ($pwd['is_revoked']) {
                                    echo '<span class="badge blocked">Bekor</span>';
                                } elseif ($pwd['is_used']) {
                                    echo '<span class="badge active">Ishlatildi</span>';
                                } elseif (strtotime($pwd['expires_at']) < time()) {
                                    echo '<span class="badge blocked">Tugagan</span>';
                                } else {
                                    echo '<span class="badge pending">Faol</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if (!$pwd['is_revoked'] && !$pwd['is_used'] && strtotime($pwd['expires_at']) > time()) { ?>
                                <button class="btn secondary" style="padding: 5px 10px; font-size: 12px;"
                                    onclick="revokePassword(<?php echo $pwd['id']; ?>)">
                                    Bekor qilish
                                </button>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
                
            <?php
            }
            
            // ==================== DEVICES ====================
            elseif ($page === 'devices') {
                $db->query("
                    SELECT df.*, u.username, COUNT(lh.id) as logins
                    FROM device_fingerprints df
                    JOIN users u ON df.user_id = u.id
                    LEFT JOIN login_history lh ON df.id = lh.device_id
                    GROUP BY df.id
                    ORDER BY df.last_seen DESC
                ");
                $devices = $db->resultSet();
                ?>
                
                <table>
                    <thead>
                        <tr>
                            <th>Foydalanuvchi</th>
                            <th>Qurilma</th>
                            <th>Browser</th>
                            <th>OS</th>
                            <th>IP</th>
                            <th>Oxirgi Kirish</th>
                            <th>Kirushlar</th>
                            <th>Status</th>
                            <th>Amallar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($devices as $device) { ?>
                        <tr>
                            <td><?php echo $device['username']; ?></td>
                            <td><?php echo $device['device_name']; ?></td>
                            <td><?php echo $device['browser']; ?></td>
                            <td><?php echo $device['os']; ?></td>
                            <td><?php echo $device['ip_address']; ?></td>
                            <td><?php echo date('d.m.Y H:i', strtotime($device['last_seen'])); ?></td>
                            <td><?php echo $device['logins']; ?></td>
                            <td>
                                <?php if ($device['is_approved']) { ?>
                                    <span class="badge active">✓ Tasdiq</span>
                                <?php } else { ?>
                                    <span class="badge pending">⏳ Kutilmoqda</span>
                                <?php } ?>
                            </td>
                            <td>
                                <?php if (!$device['is_approved']) { ?>
                                <button class="btn" style="padding: 5px 10px; font-size: 12px;"
                                    onclick="approveDevice(<?php echo $device['id']; ?>)">
                                    ✓ Tasdiq
                                </button>
                                <button class="btn secondary" style="padding: 5px 10px; font-size: 12px;"
                                    onclick="rejectDevice(<?php echo $device['id']; ?>)">
                                    ✗ Rad
                                </button>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
                
            <?php
            }
            
            // ==================== PUBLIC MOVIES ====================
            elseif ($page === 'public-movies') {
                ?>
                
                <h3 style="margin-bottom: 20px;">Yangi Public Kino Qo'shish</h3>
                <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; max-width: 600px;">
                    <form id="createMovieForm">
                        <div class="form-group">
                            <label>Kino Nomi</label>
                            <input type="text" id="movieTitle" required>
                        </div>
                        <div class="form-group">
                            <label>Tavsif</label>
                            <textarea id="movieDesc" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Kategoriya</label>
                            <select id="movieCategory" required>
                                <option value="">-- Tanlang --</option>
                                <?php
                                $db->query("SELECT * FROM categories ORDER BY name");
                                foreach ($db->resultSet() as $cat) {
                                    echo '<option value="' . $cat['id'] . '">' . $cat['name'] . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <button type="button" class="btn" onclick="createMovie()">Kino Qo'shish</button>
                    </form>
                </div>
                
                <h3 style="margin-bottom: 20px;">Mavjud Kinolar</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Nomi</th>
                            <th>Kategoriya</th>
                            <th>Ko'rildi</th>
                            <th>Rating</th>
                            <th>Yaratildi</th>
                            <th>Status</th>
                            <th>Amallar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $db->query("
                            SELECT m.*, c.name as category_name
                            FROM movies m
                            LEFT JOIN categories c ON m.category_id = c.id
                            ORDER BY m.created_at DESC
                            LIMIT 50
                        ");
                        foreach ($db->resultSet() as $movie) {
                        ?>
                        <tr>
                            <td><?php echo Security::escape($movie['title']); ?></td>
                            <td><?php echo $movie['category_name']; ?></td>
                            <td><?php echo $movie['views']; ?></td>
                            <td><?php echo $movie['rating']; ?></td>
                            <td><?php echo date('d.m.Y', strtotime($movie['created_at'])); ?></td>
                            <td>
                                <span class="badge <?php echo $movie['is_published'] ? 'active' : 'blocked'; ?>">
                                    <?php echo $movie['is_published'] ? '✓ Nashr' : '✗ Qoralama'; ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn secondary" style="padding: 5px 10px; font-size: 12px;"
                                    onclick="deleteMovie(<?php echo $movie['id']; ?>)">
                                    🗑️ O'chirish
                                </button>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
                
            <?php
            }
            
            // ==================== PRIVATE MOVIES ====================
            elseif ($page === 'private-movies') {
                ?>
                
                <h3 style="margin-bottom: 20px;">Yangi Yopiq Kino Qo'shish</h3>
                <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; max-width: 600px;">
                    <form id="createPrivateMovieForm">
                        <div class="form-group">
                            <label>Kino Nomi</label>
                            <input type="text" id="privateMovieTitle" required>
                        </div>
                        <div class="form-group">
                            <label>Tavsif</label>
                            <textarea id="privateMovieDesc" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Kategoriya</label>
                            <select id="privateMovieCategory" required>
                                <option value="">-- Tanlang --</option>
                                <?php
                                $db->query("SELECT * FROM categories ORDER BY name");
                                foreach ($db->resultSet() as $cat) {
                                    echo '<option value="' . $cat['id'] . '">' . $cat['name'] . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <button type="button" class="btn" onclick="createPrivateMovie()">Yopiq Kino Qo'shish</button>
                    </form>
                </div>
                
                <h3 style="margin-bottom: 20px;">Mavjud Yopiq Kinolar</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Nomi</th>
                            <th>Kategoriya</th>
                            <th>Ko'rildi</th>
                            <th>Rating</th>
                            <th>Yaratildi</th>
                            <th>Amallar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $db->query("
                            SELECT m.*, c.name as category_name
                            FROM private_movies m
                            LEFT JOIN categories c ON m.category_id = c.id
                            ORDER BY m.created_at DESC
                            LIMIT 50
                        ");
                        foreach ($db->resultSet() as $movie) {
                        ?>
                        <tr>
                            <td><?php echo Security::escape($movie['title']); ?></td>
                            <td><?php echo $movie['category_name']; ?></td>
                            <td><?php echo $movie['views']; ?></td>
                            <td><?php echo $movie['rating']; ?></td>
                            <td><?php echo date('d.m.Y', strtotime($movie['created_at'])); ?></td>
                            <td>
                                <button class="btn secondary" style="padding: 5px 10px; font-size: 12px;"
                                    onclick="deletePrivateMovie(<?php echo $movie['id']; ?>)">
                                    🗑️ O'chirish
                                </button>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
                
            <?php
            }
            
            // ==================== LOGS ====================
            elseif ($page === 'logs') {
                $db->query("
                    SELECT al.*, u.username
                    FROM admin_logs al
                    JOIN users u ON al.admin_id = u.id
                    ORDER BY al.created_at DESC
                    LIMIT 100
                ");
                $logs = $db->resultSet();
                ?>
                
                <table>
                    <thead>
                        <tr>
                            <th>Admin</th>
                            <th>Harakat</th>
                            <th>Maqsad</th>
                            <th>Vaqt</th>
                            <th>IP</th>
                            <th>Tafsilotlar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log) { ?>
                        <tr>
                            <td><?php echo $log['username']; ?></td>
                            <td><?php echo $log['action']; ?></td>
                            <td><?php echo $log['target_type'] . ' (#' . $log['target_id'] . ')'; ?></td>
                            <td><?php echo date('d.m.Y H:i:s', strtotime($log['created_at'])); ?></td>
                            <td><?php echo $log['ip_address']; ?></td>
                            <td>
                                <small><?php echo substr($log['changes'], 0, 50); ?>...</small>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
                
            <?php
            }
            ?>
        </div>
    </div>
    
    <script>
        // API calls
        async function apiCall(action, data) {
            const response = await fetch('/admin/index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: action,
                    ...data
                })
            });
            
            return await response.json();
        }
        
        // Create user
        async function createUser() {
            const username = document.getElementById('username').value;
            const email = document.getElementById('email').value;
            
            if (!username || !email) {
                alert('Barcha maydonlarni to\'ldiring');
                return;
            }
            
            const result = await apiCall('create_user', { username, email });
            
            if (result.success) {
                alert('Foydalanuvchi yaratildi! ID: ' + result.user_id);
                document.getElementById('username').value = '';
                document.getElementById('email').value = '';
            } else {
                alert('Xato: ' + result.message);
            }
        }
        
        // Set temporary password
        async function setTemporaryPassword() {
            const userId = document.getElementById('userSelect').value;
            const expiresDays = document.getElementById('expiresDays').value;
            
            if (!userId) {
                alert('Foydalanuvchi tanlang');
                return;
            }
            
            const result = await apiCall('set_temp_password', {
                user_id: userId,
                expires_in_days: expiresDays
            });
            
            if (result.success) {
                alert('✓ Parol yaratildi!\n\nParol: ' + result.password + '\n\nTugaydi: ' + result.expires_at);
                document.getElementById('userSelect').value = '';
                location.reload();
            } else {
                alert('Xato: ' + result.message);
            }
        }
        
        // Revoke password
        async function revokePassword(passwordId) {
            if (!confirm('Parolni bekor qilasizmi?')) return;
            
            const result = await apiCall('revoke_password', { password_id: passwordId });
            
            if (result.success) {
                alert('Parol bekor qilindi');
                location.reload();
            } else {
                alert('Xato: ' + result.message);
            }
        }
        
        // Approve device
        async function approveDevice(deviceId) {
            if (!confirm('Qurilmani tasdiqlamasiz?')) return;
            
            const result = await apiCall('approve_device', { device_id: deviceId });
            
            if (result.success) {
                alert('Qurilma tasdiqlandı');
                location.reload();
            } else {
                alert('Xato: ' + result.message);
            }
        }
        
        // Reject device
        async function rejectDevice(deviceId) {
            if (!confirm('Qurilmani radasizmi?')) return;
            
            const result = await apiCall('reject_device', { device_id: deviceId });
            
            if (result.success) {
                alert('Qurilma rad qilindi');
                location.reload();
            } else {
                alert('Xato: ' + result.message);
            }
        }
        
        // Toggle user status
        async function toggleUserStatus(userId, currentStatus) {
            const action = currentStatus === 'active' ? 'block_user' : 'unblock_user';
            const result = await apiCall(action, { user_id: userId });
            
            if (result.success) {
                alert(result.message);
                location.reload();
            } else {
                alert('Xato: ' + result.message);
            }
        }
        
        // Show user monitoring
        function showUserMonitoring(userId) {
            window.location.href = '?page=users&monitor=' + userId;
        }
        
        // Create movie
        async function createMovie() {
            const title = document.getElementById('movieTitle').value;
            const category = document.getElementById('movieCategory').value;
            
            if (!title || !category) {
                alert('Majburiy maydonlarni to\'ldiring');
                return;
            }
            
            const result = await apiCall('create_movie', {
                title: title,
                description: document.getElementById('movieDesc').value,
                category_id: category,
                thumbnail: '',
                poster: '',
                duration: 0,
                rating: 0,
                release_date: new Date().toISOString().split('T')[0]
            });
            
            if (result.success) {
                alert('Kino yaratildi!');
                location.reload();
            } else {
                alert('Xato: ' + result.message);
            }
        }
        
        // Create private movie
        async function createPrivateMovie() {
            const title = document.getElementById('privateMovieTitle').value;
            const category = document.getElementById('privateMovieCategory').value;
            
            if (!title || !category) {
                alert('Majburiy maydonlarni to\'ldiring');
                return;
            }
            
            const result = await apiCall('create_private_movie', {
                title: title,
                description: document.getElementById('privateMovieDesc').value,
                category_id: category,
                thumbnail: '',
                poster: '',
                duration: 0,
                rating: 0,
                release_date: new Date().toISOString().split('T')[0]
            });
            
            if (result.success) {
                alert('Yopiq kino yaratildi!');
                location.reload();
            } else {
                alert('Xato: ' + result.message);
            }
        }
        
        // Delete movie
        async function deleteMovie(movieId) {
            if (!confirm('Kinoni o\'chirish rostanmi?')) return;
            
            const result = await apiCall('delete_movie', { movie_id: movieId });
            
            if (result.success) {
                alert('Kino o\'chirildi');
                location.reload();
            } else {
                alert('Xato: ' + result.message);
            }
        }
        
        // Delete private movie
        async function deletePrivateMovie(movieId) {
            if (!confirm('Yopiq kinoni o\'chirish rostanmi?')) return;
            
            const result = await apiCall('delete_movie', { movie_id: movieId });
            
            if (result.success) {
                alert('Kino o\'chirildi');
                location.reload();
            } else {
                alert('Xato: ' + result.message);
            }
        }
    </script>
</body>
</html>
