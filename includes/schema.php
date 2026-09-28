<?php
// ==========================================
// AUTO-MIGRATE DATABASE SCHEMA
// Runs safely on every request (fast checks, one-time creates)
// ==========================================
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS videos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NULL,
        embed_code TEXT,
        video_url TEXT,
        thumbnail_url TEXT,
        description TEXT,
        views INT DEFAULT 0,
        likes INT DEFAULT 0,
        status ENUM('published','draft') DEFAULT 'published',
        category_id INT NULL,
        is_featured TINYINT(1) DEFAULT 0,
        meta_title VARCHAR(255) NULL,
        meta_description VARCHAR(500) NULL,
        meta_keywords VARCHAR(500) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $columns = $pdo->query("DESCRIBE videos")->fetchAll(PDO::FETCH_COLUMN);
    $adds = [
        'slug' => "ADD COLUMN slug VARCHAR(255) NULL",
        'embed_code' => "ADD COLUMN embed_code TEXT",
        'likes' => "ADD COLUMN likes INT DEFAULT 0",
        'status' => "ADD COLUMN status ENUM('published','draft') DEFAULT 'published'",
        'category_id' => "ADD COLUMN category_id INT NULL",
        'is_featured' => "ADD COLUMN is_featured TINYINT(1) DEFAULT 0",
        'meta_title' => "ADD COLUMN meta_title VARCHAR(255) NULL",
        'meta_description' => "ADD COLUMN meta_description VARCHAR(500) NULL",
        'meta_keywords' => "ADD COLUMN meta_keywords VARCHAR(500) NULL",
        'tags' => "ADD COLUMN tags VARCHAR(500) NULL",
    ];
    foreach ($adds as $col => $sql) {
        if (!in_array($col, $columns)) $pdo->exec("ALTER TABLE videos $sql");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS video_categories (
        video_id INT NOT NULL,
        category_id INT NOT NULL,
        PRIMARY KEY (video_id, category_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Backfill junction table from legacy category_id once
    if (!in_array('category_id', $columns)) $pdo->exec("ALTER TABLE videos ADD COLUMN category_id INT NULL");
    try {
        $jc = $pdo->query("SELECT COUNT(*) FROM video_categories")->fetchColumn();
        if (!$jc) $pdo->exec("INSERT IGNORE INTO video_categories (video_id, category_id) SELECT id, category_id FROM videos WHERE category_id IS NOT NULL");
    } catch (Exception $e) {}

    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        slug VARCHAR(120) NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $cat_cols = $pdo->query("DESCRIBE categories")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('slug', $cat_cols)) $pdo->exec("ALTER TABLE categories ADD COLUMN slug VARCHAR(120) NULL");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('user','admin') DEFAULT 'user',
        status ENUM('active','banned') DEFAULT 'active',
        avatar_color VARCHAR(7) DEFAULT '#8b5cf6',
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        video_id INT NOT NULL,
        user_id INT NULL,
        user_name VARCHAR(100) NOT NULL,
        comment_text TEXT NOT NULL,
        likes INT DEFAULT 0,
        status ENUM('approved','pending','spam') DEFAULT 'approved',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $c_cols = $pdo->query("DESCRIBE comments")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('likes', $c_cols)) $pdo->exec("ALTER TABLE comments ADD COLUMN likes INT DEFAULT 0");
    if (!in_array('user_id', $c_cols)) $pdo->exec("ALTER TABLE comments ADD COLUMN user_id INT NULL");
    if (!in_array('status', $c_cols)) $pdo->exec("ALTER TABLE comments ADD COLUMN status ENUM('approved','pending','spam') DEFAULT 'approved'");

    $pdo->exec("CREATE TABLE IF NOT EXISTS video_likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        video_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_like (video_id, user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS watch_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        video_id INT NOT NULL,
        user_id INT NOT NULL,
        watched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_watch (video_id, user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS ads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        placement ENUM('header','footer','below_player','between_grid','sidebar','popup','popunder','custom') DEFAULT 'custom',
        ad_code TEXT,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS pages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        slug VARCHAR(200) NOT NULL UNIQUE,
        content MEDIUMTEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Seed default admin user
    $admin_exists = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    if (!$admin_exists) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, email, password_hash, role) VALUES ('admin', ?, ?, 'admin')");
        $stmt->execute([DEFAULT_ADMIN_EMAIL, password_hash(DEFAULT_ADMIN_PASS, PASSWORD_DEFAULT)]);
    }
} catch (Exception $e) {
    // Schema migration fallback - never break the site
}
