CREATE DATABASE IF NOT EXISTS dramamini;
USE dramamini;

-- USERS TABLE
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'blocked') DEFAULT 'active'
);

-- TEMPORARY PASSWORDS
CREATE TABLE temp_passwords (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    used_at TIMESTAMP NULL,
    is_revoked BOOLEAN DEFAULT FALSE,
    revoked_at TIMESTAMP NULL,
    revoked_by INT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (revoked_by) REFERENCES users(id)
);

-- DEVICE FINGERPRINTS
CREATE TABLE device_fingerprints (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    device_fingerprint VARCHAR(255) UNIQUE NOT NULL,
    device_name VARCHAR(100),
    browser VARCHAR(100),
    os VARCHAR(100),
    ip_address VARCHAR(45),
    is_approved BOOLEAN DEFAULT FALSE,
    approved_at TIMESTAMP NULL,
    approved_by INT NULL,
    first_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- SESSIONS
CREATE TABLE sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    device_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (device_id) REFERENCES device_fingerprints(id) ON DELETE CASCADE
);

-- LOGIN HISTORY
CREATE TABLE login_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    device_id INT NULL,
    username VARCHAR(50),
    ip_address VARCHAR(45),
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    logout_time TIMESTAMP NULL,
    success BOOLEAN DEFAULT TRUE,
    failure_reason VARCHAR(255) NULL,
    browser_info VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (device_id) REFERENCES device_fingerprints(id) ON DELETE SET NULL
);

-- CATEGORIES
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- PUBLIC MOVIES
CREATE TABLE movies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    thumbnail VARCHAR(255),
    poster VARCHAR(255),
    category_id INT,
    duration INT,
    rating DECIMAL(3,1),
    views INT DEFAULT 0,
    is_published BOOLEAN DEFAULT TRUE,
    release_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- EPISODES
CREATE TABLE episodes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    movie_id INT NOT NULL,
    episode_number INT NOT NULL,
    title VARCHAR(255),
    description TEXT,
    video_url VARCHAR(500),
    duration INT,
    views INT DEFAULT 0,
    release_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
);

-- PRIVATE MOVIES
CREATE TABLE private_movies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    thumbnail VARCHAR(255),
    poster VARCHAR(255),
    category_id INT,
    duration INT,
    rating DECIMAL(3,1),
    views INT DEFAULT 0,
    is_published BOOLEAN DEFAULT TRUE,
    release_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- PRIVATE EPISODES
CREATE TABLE private_episodes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    movie_id INT NOT NULL,
    episode_number INT NOT NULL,
    title VARCHAR(255),
    description TEXT,
    video_url VARCHAR(500),
    duration INT,
    views INT DEFAULT 0,
    release_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (movie_id) REFERENCES private_movies(id) ON DELETE CASCADE
);

-- WATCHLIST
CREATE TABLE watchlist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    movie_id INT,
    private_movie_id INT,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (private_movie_id) REFERENCES private_movies(id) ON DELETE CASCADE
);

-- WATCH HISTORY (FIXED - current_time -> watched_position)
CREATE TABLE watch_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    episode_id INT,
    private_episode_id INT,
    watched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    watched_position INT DEFAULT 0,
    duration INT,
    is_completed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ADMIN LOGS
CREATE TABLE admin_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    action VARCHAR(255),
    target_type VARCHAR(50),
    target_id INT,
    changes JSON,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id)
);

-- RATE LIMIT
CREATE TABLE rate_limit (
    id INT PRIMARY KEY AUTO_INCREMENT,
    identifier VARCHAR(255),
    endpoint VARCHAR(255),
    attempt_count INT DEFAULT 1,
    first_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- INSERT DEFAULT DATA
INSERT INTO categories (name, slug, description) VALUES
('Koreyska Drama', 'korean', 'Qo\'shni Koreya seri dramalari'),
('Xitoy Dramalari', 'chinese', 'Xitoy seri dramalari'),
('Turk Dramalari', 'turkish', 'Turk seri dramalari'),
('Kino', 'movies', 'Kinolar va filmlar'),
('Romantika', 'romance', 'Romantik Drama'),
('Fantastika', 'fantasy', 'Fantastik Drama');

INSERT INTO users (username, email, is_admin) VALUES ('admin', 'admin@dramamini.net', TRUE);