<?php
// ==================== API ENDPOINTS ====================

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Security.php';
require_once __DIR__ . '/../includes/Movie.php';

Security::setSecurityHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api/', '', $path);
$parts = explode('/', trim($path, '/'));
$endpoint = $parts[0] ?? '';

$db = new Database();

// ==================== LOGIN ====================
if ($endpoint === 'login' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // CSRF token tekshirish (optional, form data uchun)
    if (!isset($input['username']) || !isset($input['password'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Username va password kerak'
        ]);
        exit;
    }
    
    $auth = new Auth($db);
    $result = $auth->login($input['username'], $input['password']);
    
    echo json_encode($result);
    exit;
}

// ==================== LOGOUT ====================
if ($endpoint === 'logout' && $method === 'POST') {
    $auth = new Auth($db);
    $auth->logout();
    
    echo json_encode([
        'success' => true,
        'message' => 'Logout qilindi'
    ]);
    exit;
}

// ==================== PUBLIC MOVIES ====================
if ($endpoint === 'movies' && $method === 'GET') {
    $page = $_GET['page'] ?? 1;
    $category = $_GET['category'] ?? null;
    $movie = new Movie($db);
    
    $movies = $movie->getAllPublicMovies($page, 20, $category);
    
    echo json_encode([
        'success' => true,
        'data' => $movies
    ]);
    exit;
}

// ==================== SINGLE MOVIE ====================
if ($endpoint === 'movie' && $method === 'GET') {
    $movieId = $_GET['id'] ?? null;
    
    if (!$movieId) {
        echo json_encode(['success' => false, 'message' => 'ID kerak']);
        exit;
    }
    
    $movie = new Movie($db);
    $movieData = $movie->getPublicMovie($movieId);
    $episodes = $movieData ? $movie->getMovieEpisodes($movieId) : [];
    
    echo json_encode([
        'success' => !!$movieData,
        'movie' => $movieData,
        'episodes' => $episodes
    ]);
    exit;
}

// ==================== SEARCH ====================
if ($endpoint === 'search' && $method === 'GET') {
    $query = $_GET['q'] ?? '';
    
    if (strlen($query) < 2) {
        echo json_encode([
            'success' => false,
            'message' => 'Minimum 2 ta harf kiriting'
        ]);
        exit;
    }
    
    $movie = new Movie($db);
    $results = $movie->searchMovies($query);
    
    echo json_encode([
        'success' => true,
        'results' => $results,
        'count' => count($results)
    ]);
    exit;
}

// ==================== PRIVATE MOVIES (PROTECTED) ====================
if ($endpoint === 'private-movies' && $method === 'GET') {
    $auth = new Auth($db);
    $session = $auth->checkSession();
    
    if (!$session) {
        echo json_encode([
            'success' => false,
            'message' => 'Login qiling'
        ]);
        exit;
    }
    
    $page = $_GET['page'] ?? 1;
    
    // ✅ FIX: Private movies uchun to'g'ri query
    $db->query("
        SELECT pm.*, c.name as category_name 
        FROM private_movies pm
        LEFT JOIN categories c ON pm.category_id = c.id
        WHERE pm.is_published = TRUE
        ORDER BY pm.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $perPage = 20;
    $offset = ($page - 1) * $perPage;
    $db->bind('i', $perPage);
    $db->bind('i', $offset);
    $privateMovies = $db->resultSet();
    
    echo json_encode([
        'success' => true,
        'data' => $privateMovies,
        'message' => 'Yopiq bo\'lim kinolari'
    ]);
    exit;
}

// ==================== CATEGORIES ====================
if ($endpoint === 'categories' && $method === 'GET') {
    $db->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $db->resultSet();
    
    echo json_encode([
        'success' => true,
        'data' => $categories
    ]);
    exit;
}

// ==================== USER PROFILE (PROTECTED) ====================
if ($endpoint === 'profile' && $method === 'GET') {
    $auth = new Auth($db);
    $session = $auth->checkSession();
    
    if (!$session) {
        echo json_encode([
            'success' => false,
            'message' => 'Login qiling'
        ]);
        exit;
    }
    
    $db->query("SELECT id, username, email, is_admin, created_at FROM users WHERE id = ?");
    $db->bind('i', $session['user_id']);
    $user = $db->single();
    
    echo json_encode([
        'success' => true,
        'user' => $user
    ]);
    exit;
}

// ==================== WATCHLIST MANAGEMENT (PROTECTED) ====================
if ($endpoint === 'watchlist' && $method === 'GET') {
    $auth = new Auth($db);
    $session = $auth->checkSession();
    
    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Login qiling']);
        exit;
    }
    
    $db->query("
        SELECT * FROM watchlist 
        WHERE user_id = ?
        ORDER BY added_at DESC
    ");
    $db->bind('i', $session['user_id']);
    $watchlist = $db->resultSet();
    
    echo json_encode([
        'success' => true,
        'data' => $watchlist
    ]);
    exit;
}

// ==================== ADD TO WATCHLIST ====================
if ($endpoint === 'watchlist-add' && $method === 'POST') {
    $auth = new Auth($db);
    $session = $auth->checkSession();
    
    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Login qiling']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $movieId = $input['movie_id'] ?? null;
    $isPrivate = $input['is_private'] ?? false;
    
    $db->query("
        INSERT INTO watchlist (user_id, movie_id, private_movie_id)
        VALUES (?, ?, ?)
    ");
    $db->bind('i', $session['user_id']);
    $db->bind('i', $isPrivate ? null : $movieId);
    $db->bind('i', $isPrivate ? $movieId : null);
    
    echo json_encode(['success' => true, 'message' => 'Watchlist\'ga qo\'shildi']);
    exit;
}

// ==================== WATCH HISTORY ====================
if ($endpoint === 'watch-history' && $method === 'POST') {
    $auth = new Auth($db);
    $session = $auth->checkSession();
    
    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Login qiling']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // ✅ FIX: Column name - "current_time" o'rniga "watched_position"
    $db->query("
        INSERT INTO watch_history (user_id, episode_id, watched_position, duration, is_completed)
        VALUES (?, ?, ?, ?, ?)
    ");
    $db->bind('i', $session['user_id']);
    $db->bind('i', $input['episode_id']);
    $db->bind('i', $input['watched_position'] ?? 0);
    $db->bind('i', $input['duration']);
    $db->bind('i', $input['is_completed'] ? 1 : 0);
    
    echo json_encode(['success' => true, 'message' => 'Ko\'rish tarixiga saqlandi']);
    exit;
}

// ==================== DEFAULT ====================
echo json_encode([
    'success' => false,
    'message' => 'API endpoint topilmadi'
]);
?>
