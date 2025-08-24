<?php
// MySQL數據庫配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'workt');
define('DB_USER', 'workt');
define('DB_PASS', '4P7wf3n8inXCp4pZ');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("數據庫連接失敗: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    // 初始化數據庫表
    public function initTables() {
        $sql = "
        CREATE TABLE IF NOT EXISTS assignments (
            id VARCHAR(50) PRIMARY KEY,
            group_name VARCHAR(100) NOT NULL,
            student_name VARCHAR(100) NOT NULL,
            title VARCHAR(200) NOT NULL,
            url VARCHAR(500) NOT NULL,
            submitter_cookie VARCHAR(100) NOT NULL,
            submit_time DATETIME NOT NULL,
            edit_time DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            classroom_id INT(11) NULL,
            score DECIMAL(5,2) NULL,
            score_comment TEXT NULL,
            showcase_status ENUM('pending','approved','rejected','auto') DEFAULT 'pending',
            is_featured TINYINT(1) DEFAULT 0,
            is_public TINYINT(1) DEFAULT 0,
            INDEX idx_classroom_id (classroom_id),
            INDEX idx_score (score),
            INDEX idx_showcase_status (showcase_status),
            INDEX idx_is_featured (is_featured)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        
        CREATE TABLE IF NOT EXISTS config (
            config_key VARCHAR(100) PRIMARY KEY,
            config_value TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        
        CREATE TABLE IF NOT EXISTS classrooms (
            id INT(11) PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            description TEXT NULL,
            share_code VARCHAR(20) NOT NULL UNIQUE,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            password VARCHAR(255) NULL,
            require_password TINYINT(1) DEFAULT 0,
            INDEX idx_share_code (share_code),
            INDEX idx_is_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        
        CREATE TABLE IF NOT EXISTS student_classroom_access (
            id INT(11) PRIMARY KEY AUTO_INCREMENT,
            student_cookie VARCHAR(100) NOT NULL,
            classroom_id INT(11) NOT NULL,
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_student_cookie (student_cookie),
            INDEX idx_classroom_id (classroom_id),
            UNIQUE KEY unique_student_classroom (student_cookie, classroom_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $this->pdo->exec($sql);
        
        // 插入默認配置和數據
        $this->insertDefaultConfig();
        $this->insertDefaultClassroom();
    }
    
    private function insertDefaultConfig() {
        $configs = [
            'school_name' => '臺北市幼華高級中學',
            'platform_title' => '網站實作作業平台',
            'admin_username' => 'admin',
            'admin_password' => 'admin123456',
            'max_score' => '100',
            'score_visibility' => 'private',
            'allow_score_comments' => '1',
            'enable_featured' => '1',
            'default_classroom' => ''
        ];
        
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO config (config_key, config_value) VALUES (?, ?)");
        
        foreach ($configs as $key => $value) {
            $stmt->execute([$key, $value]);
        }
    }
    
    private function insertDefaultClassroom() {
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO classrooms (name, description, share_code, is_active, require_password, password) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['預設教室', '系統預設教室，用於展示作業功能', 'DEFAULT2025', 1, 0, null]);
    }
}

// 數據庫助手函數
function getDB() {
    return Database::getInstance()->getConnection();
}

// 初始化數據庫
function initDatabase() {
    Database::getInstance()->initTables();
}
?>
