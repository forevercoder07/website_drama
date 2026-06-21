<?php
session_start();

// ✅ FIX: Noto'g'ri file yo'llar - admin/index.php, admin/login.php, api/index.php qo'shimcha kerak emas!
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Security.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Movie.php';

$db = new Database();
$auth = new Auth($db);
$session = $auth->checkSession();

$page = $_GET['page'] ?? 'home';
$type = $_GET['type'] ?? null;

?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DramaMini - Koreyska, Xitoy va Turk Dramalari</title>
    
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0a0e27;
            color: #fff;
        }
        
        /* ==================== HEADER ==================== */
        
        header {
            background: linear-gradient(90deg, #0a0e27 0%, #1a1f3a 100%);
            border-bottom: 2px solid #ff4444;
            padding: 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.5);
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
        }
        
        .logo {
            font-size: 28px;
            font-weight: bold;
            background: linear-gradient(135deg, #ff4444, #ff8800);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            cursor: pointer;
        }
        
        .logo:hover {
            transform: scale(1.05);
            transition: transform 0.3s;
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
        }
        
        .nav-links a {
            color: #ddd;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .nav-links a:hover,
        .nav-links a.active {
            color: #ff4444;
        }
        
        .user-menu {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .user-menu .btn {
            background: #ff4444;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .user-menu .btn:hover {
            background: #cc0000;
            transform: translateY(-2px);
        }
        
        .search-box {
            display: flex;
            gap: 10px;
            padding: 15px 30px;
            background: rgba(0,0,0,0.3);
        }
        
        .search-box input {
            flex: 1;
            padding: 10px 15px;
            background: rgba(255,255,255,0.1);
            border: 1px solid #ff4444;
            border-radius: 5px;
            color: white;
            font-size: 14px;
        }
        
        .search-box input::placeholder {
            color: #999;
        }
        
        .search-box button {
            background: #ff4444;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        /* ==================== CATEGORIES ==================== */
        
        .categories {
            display: flex;
            gap: 10px;
            padding: 15px 30px;
            overflow-x: auto;
            background: rgba(0,0,0,0.2);
        }
        
        .categories a {
            white-space: nowrap;
            padding: 8px 15px;
            background: rgba(255,68,68,0.2);
            color: #ff4444;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .categories a:hover,
        .categories a.active {
            background: #ff4444;
            color: white;
        }
        
        /* ==================== MAIN ==================== */
        
        main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }
        
        h2 {
            font-size: 24px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ff4444;
        }
        
        /* ==================== MOVIE GRID ==================== */
        
        .movies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .movie-card {
            background: #1a1f3a;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid #2a2f4a;
        }
        
        .movie-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(255,68,68,0.3);
        }
        
        .movie-poster {
            width: 100%;
            height: 250px;
            background: linear-gradient(135deg, #ff4444, #ff8800);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 80px;
            position: relative;
        }
        
        .movie-poster::after {
            content: '▶';
            position: absolute;
            font-size: 50px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .movie-card:hover .movie-poster::after {
            opacity: 0.8;
        }
        
        .movie-info {
            padding: 15px;
        }
        
        .movie-info h3 {
            font-size: 16px;
            margin-bottom: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .movie-meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #aaa;
            margin-bottom: 10px;
        }
        
        .rating {
            color: #ff8800;
            font-weight: bold;
        }
        
        .views {
            color: #666;
        }
        
        .movie-actions {
            display: flex;
            gap: 8px;
            font-size: 12px;
        }
        
        .movie-actions button {
            flex: 1;
            padding: 6px;
            border: 1px solid #ff4444;
            background: transparent;
            color: #ff4444;
            border-radius: 3px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .movie-actions button:hover {
            background: #ff4444;
            color: white;
        }
        
        /* ==================== LOGIN PROMPT ==================== */
        
        .login-prompt {
            background: linear-gradient(135deg, rgba(255,68,68,0.2), rgba(255,136,0,0.2));
            border: 1px solid #ff4444;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            margin: 30px 0;
        }
        
        .login-prompt h3 {
            font-size: 22px;
            margin-bottom: 15px;
        }
        
        .login-prompt p {
            color: #aaa;
            margin-bottom: 20px;
        }
        
        .login-prompt button {
            background: #ff4444;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .login-prompt button:hover {
            background: #cc0000;
        }
        
        /* ==================== RESPONSIVE ==================== */
        
        @media (max-width: 768px) {
            .header-top {
                padding: 15px;
            }
            
            .nav-links {
                display: none;
            }
            
            main {
                padding: 15px;
            }
            
            .movies-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 15px;
            }
            
            .movie-poster {
                height: 200px;
            }
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <header>
        <div class="header-top">
            <div class="logo" onclick="window.location.href='/'">🎬 DramaMini</div>
            <div class="nav-links">
                <a href="/" class="<?php echo $page === 'home' ? 'active' : ''; ?>">Bosh sahifa</a>
                <a href="/?type=series" class="<?php echo $type === 'series' ? 'active' : ''; ?>">Drama</a>
                <a href="/?type=movie" class="<?php echo $type === 'movie' ? 'active' : ''; ?>">Kino</a>
                <?php if ($session) { ?>
                    <a href="/?page=private">🔒 Yopiq Bo'lim</a>
                    <a href="/?page=watchlist">❤️ My List</a>
                <?php } ?>
            </div>
            <div class="user-menu">
                <?php if ($session) { ?>
                    <span>👤 <?php echo Security::escape($_SESSION['username']); ?></span>
                    <button class="btn" onclick="logout()">Logout</button>
                <?php } else { ?>
                    <button class="btn" onclick="window.location.href='/private/login.php'">Login</button>
                <?php } ?>
            </div>
        </div>
        
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Kino yoki drama qidirish...">
            <button onclick="searchMovies()">🔍 Qidirish</button>
        </div>
        
        <div class="categories">
            <a href="/" class="<?php echo !$type ? 'active' : ''; ?>">Barchasi</a>
            <a href="/?type=series" class="<?php echo $type === 'series' ? 'active' : ''; ?>">📺 Drama</a>
            <a href="/?type=movie" class="<?php echo $type === 'movie' ? 'active' : ''; ?>">🎬 Kino</a>
            <a href="/?category=1">💚 Koreyska</a>
            <a href="/?category=2">🏯 Xitoy</a>
            <a href="/?category=3">🌹 Turk</a>
            <a href="/?category=5">💕 Romantika</a>
            <a href="/?category=6">✨ Fantastika</a>
        </div>
    </header>
    
    <!-- MAIN CONTENT -->
    <main>
        <?php
        
        // HOME PAGE
        if ($page === 'home') {
        ?>
            <h2>🔥 Yangi va Ommabop</h2>
            <div class="movies-grid" id="moviesContainer">
                <!-- Movies ko'rsatiladi JavaScript orqali -->
            </div>
        
        <!-- YOPIQ BO'LIM -->
        <?php } elseif ($page === 'private') {
            if (!$session) {
        ?>
            <div class="login-prompt">
                <h3>🔒 Yopiq Bo'limga Kirish</h3>
                <p>Eksklyuziv kontent ko'rish uchun iltimos login qiling</p>
                <button onclick="window.location.href='/private/login.php'">Kirish</button>
            </div>
        <?php
            } else {
        ?>
            <h2>🔒 Yopiq Bo'lim Kinolari</h2>
            <div class="movies-grid" id="privateMoviesContainer">
                <!-- Private movies ko'rsatiladi -->
            </div>
        <?php
            }
        }
        
        // WATCHLIST
        elseif ($page === 'watchlist') {
            if (!$session) {
        ?>
            <div class="login-prompt">
                <h3>❤️ My List</h3>
                <p>Sevimli kinolaringiz ro'yxati ko'rish uchun login qiling</p>
                <button onclick="window.location.href='/private/login.php'">Kirish</button>
            </div>
        <?php
            } else {
        ?>
            <h2>❤️ Mening Sevimli Kinolari</h2>
            <div class="movies-grid" id="watchlistContainer">
                <!-- Watchlist ko'rsatiladi -->
            </div>
        <?php
            }
        }
        ?>
    </main>
    
    <script>
        // Movies yuklash
        async function loadMovies() {
            const categoryId = new URLSearchParams(window.location.search).get('category');
            const url = categoryId ? 
                `/api/movies?page=1&category=${categoryId}` : 
                '/api/movies?page=1';
            
            try {
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.success && data.data) {
                    displayMovies(data.data, 'moviesContainer');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
        
        // Private movies yuklash
        async function loadPrivateMovies() {
            try {
                const response = await fetch('/api/private-movies?page=1');
                const data = await response.json();
                
                if (data.success) {
                    displayMovies(data.data, 'privateMoviesContainer');
                } else {
                    document.getElementById('privateMoviesContainer').innerHTML = 
                        '<p>Login qiling</p>';
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
        
        // Kinolarni ko'rsatish
        function displayMovies(movies, containerId) {
            const container = document.getElementById(containerId);
            container.innerHTML = '';
            
            (movies || []).forEach(movie => {
                const card = document.createElement('div');
                card.className = 'movie-card';
                card.innerHTML = `
                    <div class="movie-poster">🎬</div>
                    <div class="movie-info">
                        <h3>${movie.title || 'Noma\'lum'}</h3>
                        <div class="movie-meta">
                            <span class="rating">⭐ ${movie.rating || '0'}</span>
                            <span class="views">👁️ ${movie.views || '0'}</span>
                        </div>
                        <div class="movie-actions">
                            <button onclick="watchMovie(${movie.id})">▶ Tomosha</button>
                            <button onclick="addToWatchlist(${movie.id})">❤ Sevimli</button>
                        </div>
                    </div>
                `;
                container.appendChild(card);
            });
        }
        
        // Kinoni tomosha qilish
        function watchMovie(movieId) {
            window.location.href = `/?page=watch&id=${movieId}`;
        }
        
        // Watchlist'ga qo'shish
        async function addToWatchlist(movieId) {
            try {
                const response = await fetch('/api/watchlist-add', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ movie_id: movieId })
                });
                
                const data = await response.json();
                alert(data.message || 'Qo\'shildi');
            } catch (error) {
                alert('Xato: ' + error.message);
            }
        }
        
        // Qidirish
        function searchMovies() {
            const query = document.getElementById('searchInput').value;
            if (query) {
                window.location.href = `/?page=search&q=${encodeURIComponent(query)}`;
            }
        }
        
        // Logout
        function logout() {
            if (confirm('Logout qilasizmi?')) {
                fetch('/api/logout', { method: 'POST' }).then(() => {
                    window.location.href = '/';
                });
            }
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            const page = new URLSearchParams(window.location.search).get('page') || 'home';
            
            if (page === 'home' || !page) {
                loadMovies();
            } else if (page === 'private') {
                loadPrivateMovies();
            }
        });
    </script>

    <script src="/assets/js/script.js"></script>
    
</body>
</html>
