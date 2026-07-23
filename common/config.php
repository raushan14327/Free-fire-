<?php
/**
 * i Free Fire - Central Configuration & Database Connection
 * Host: 127.0.0.1, User: root, Pass: root
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'ifreefire');

$pdo = null;

try {
    // Attempt MySQL connection as requested
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // Seamless fallback to SQLite PDO so app runs effortlessly out-of-the-box
    $dbPath = __DIR__ . '/../ifreefire.db';
    try {
        $pdo = new PDO("sqlite:" . $dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $ex) {
        die("Database connection error: " . $ex->getMessage());
    }
}

// Ensure database tables exist automatically
function auto_init_db($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            phone VARCHAR(20) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            game_id VARCHAR(50) DEFAULT '',
            wallet_balance DECIMAL(10,2) DEFAULT 0.00,
            is_admin INT DEFAULT 0,
            status VARCHAR(20) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS tournaments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title VARCHAR(150) NOT NULL,
            category VARCHAR(50) NOT NULL,
            banner_url TEXT,
            entry_fee DECIMAL(10,2) DEFAULT 0.00,
            prize_pool DECIMAL(10,2) DEFAULT 0.00,
            per_kill_bonus DECIMAL(10,2) DEFAULT 0.00,
            max_players INT DEFAULT 100,
            match_time VARCHAR(100) NOT NULL,
            map_name VARCHAR(50) DEFAULT 'Erangel',
            game_mode VARCHAR(20) DEFAULT 'Solo',
            room_id VARCHAR(50) DEFAULT '',
            room_password VARCHAR(50) DEFAULT '',
            status VARCHAR(20) DEFAULT 'upcoming',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS tournament_participants (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tournament_id INT NOT NULL,
            user_id INT NOT NULL,
            game_username VARCHAR(100) NOT NULL,
            game_player_id VARCHAR(100) NOT NULL,
            payment_status VARCHAR(20) DEFAULT 'success',
            rank_achieved INT DEFAULT 0,
            kills_count INT DEFAULT 0,
            prize_won DECIMAL(10,2) DEFAULT 0.00,
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (tournament_id) REFERENCES tournaments(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INT NOT NULL,
            type VARCHAR(20) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_method VARCHAR(50) DEFAULT 'UPI',
            utr_reference VARCHAR(100) DEFAULT '',
            status VARCHAR(20) DEFAULT 'pending',
            remarks TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            setting_key VARCHAR(50) PRIMARY KEY,
            setting_value TEXT
        )");

        // Seed default admin if missing
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE is_admin = 1");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
            $pdo->exec("INSERT INTO users (name, email, phone, password, game_id, wallet_balance, is_admin) 
                        VALUES ('iFreeFire Admin', 'admin@ifreefire.com', '9999999999', '$adminPass', 'ADMIN_PRO', 1000.00, 1)");
        }

        // Seed default user if missing
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = 'player@ifreefire.com'");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $userPass = password_hash('player123', PASSWORD_DEFAULT);
            $pdo->exec("INSERT INTO users (name, email, phone, password, game_id, wallet_balance, is_admin) 
                        VALUES ('FF Pro Gamer', 'player@ifreefire.com', '9876543210', '$userPass', 'FF_PRO_99', 150.00, 0)");
        }

        // Seed default settings
        $defaultSettings = [
            'app_name' => 'i Free Fire',
            'support_phone' => '+91 98765 43210',
            'support_email' => 'support@ifreefire.com',
            'min_deposit' => '10',
            'min_withdrawal' => '50',
            'upi_id' => 'ifreefire@upi',
            'qr_code_url' => 'https://images.unsplash.com/photo-1628155930542-3c7a64e2c833?auto=format&fit=crop&w=400&q=80',
            'payment_instructions' => 'Pay via UPI or scan QR code. Submit your 12-digit UTR transaction reference number after payment.',
            'notice_text' => '🔥 Join Free Fire Max Tournaments Daily & Win Real Cash Rewards!'
        ];
        foreach ($defaultSettings as $k => $v) {
            $stmt = $pdo->prepare("INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$k, $v]);
        }

        // Seed sample tournaments if empty
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tournaments");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $tournaments = [
                ['Free Fire Max Bermuda Solo Rush', 'Free Fire', 'https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&w=600&q=80', 20, 1000, 15, 48, 'Today 8:00 PM', 'Bermuda', 'Solo', '', '', 'upcoming'],
                ['Free Fire Clash Squad 4v4 Championship', 'Free Fire', 'https://images.unsplash.com/photo-1511512578047-dfb367046420?auto=format&fit=crop&w=600&q=80', 50, 800, 0, 8, 'Today 9:30 PM', 'Bermuda CS', 'Squad', '', '', 'upcoming'],
                ['Free Fire Purgatory Duo Survival', 'Free Fire', 'https://images.unsplash.com/photo-1538481199705-c710c4e965fc?auto=format&fit=crop&w=600&q=80', 15, 500, 10, 48, 'Tomorrow 6:00 PM', 'Purgatory', 'Duo', '', '', 'upcoming'],
                ['Free Fire Kalahari Squad Showdown', 'Free Fire', 'https://images.unsplash.com/photo-1511512578047-dfb367046420?auto=format&fit=crop&w=600&q=80', 30, 1200, 20, 48, 'LIVE NOW', 'Kalahari', 'Squad', 'FF-ROOM-8820', '4411', 'live']
            ];
            $tStmt = $pdo->prepare("INSERT INTO tournaments (title, category, banner_url, entry_fee, prize_pool, per_kill_bonus, max_players, match_time, map_name, game_mode, room_id, room_password, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
            foreach ($tournaments as $t) {
                $tStmt->execute($t);
            }
        }

    } catch (Exception $e) {
        // Table initialization soft fail
    }
}

auto_init_db($pdo);

// Helper Functions
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function get_setting($key, $default = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    } catch (Exception $e) {
        return $default;
    }
}

function format_currency($amount) {
    return '₹' . number_format((float)$amount, 2);
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin_logged_in() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function get_logged_in_user() {
    global $pdo;
    if (!is_logged_in()) return null;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function set_flash($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $msg];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function redirect($url) {
    header("Location: $url");
    exit();
}
