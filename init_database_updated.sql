-- =====================================================
-- 數據庫表結構初始化腳本 (更新版)
-- 包含所有最新功能的表結構
-- =====================================================
-- 使用方法：
-- mysql -u workt -p4P7wf3n8inXCp4pZ workt < init_database_updated.sql
-- =====================================================

-- 設置字符集
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- 使用workt數據庫
USE workt;

-- 創建assignments表（作業表）- 包含所有新字段
CREATE TABLE IF NOT EXISTS assignments (
    id VARCHAR(50) PRIMARY KEY COMMENT '作業唯一ID',
    group_name VARCHAR(100) NOT NULL COMMENT '組別名稱',
    student_name VARCHAR(100) NOT NULL COMMENT '學生姓名',
    title VARCHAR(200) NOT NULL COMMENT '作業標題',
    url VARCHAR(500) NOT NULL COMMENT '作業網址',
    submitter_cookie VARCHAR(100) NOT NULL COMMENT '提交者Cookie',
    submit_time DATETIME NOT NULL COMMENT '提交時間',
    edit_time DATETIME NULL COMMENT '編輯時間',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '創建時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    classroom_id INT(11) NULL COMMENT '教室ID',
    score DECIMAL(5,2) NULL COMMENT '作業分數',
    score_comment TEXT NULL COMMENT '評分備註',
    showcase_status ENUM('pending','approved','rejected','auto') DEFAULT 'pending' COMMENT '展示狀態',
    is_featured TINYINT(1) DEFAULT 0 COMMENT '是否精選',
    is_public TINYINT(1) DEFAULT 0 COMMENT '是否公開',
    INDEX idx_submit_time (submit_time),
    INDEX idx_group_name (group_name),
    INDEX idx_submitter_cookie (submitter_cookie),
    INDEX idx_classroom_id (classroom_id),
    INDEX idx_score (score),
    INDEX idx_showcase_status (showcase_status),
    INDEX idx_is_featured (is_featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='作業提交表';

-- 創建config表（配置表）
CREATE TABLE IF NOT EXISTS config (
    config_key VARCHAR(100) PRIMARY KEY COMMENT '配置鍵',
    config_value TEXT NOT NULL COMMENT '配置值',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '創建時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系統配置表';

-- 創建classrooms表（教室表）
CREATE TABLE IF NOT EXISTS classrooms (
    id INT(11) PRIMARY KEY AUTO_INCREMENT COMMENT '教室ID',
    name VARCHAR(100) NOT NULL COMMENT '教室名稱',
    description TEXT NULL COMMENT '教室描述',
    share_code VARCHAR(20) NOT NULL UNIQUE COMMENT '分享代碼',
    is_active TINYINT(1) DEFAULT 1 COMMENT '是否啟用',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '創建時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    password VARCHAR(255) NULL COMMENT '教室密碼',
    require_password TINYINT(1) DEFAULT 0 COMMENT '是否需要密碼',
    INDEX idx_share_code (share_code),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='教室表';

-- 創建student_classroom_access表（學生教室訪問記錄表）
CREATE TABLE IF NOT EXISTS student_classroom_access (
    id INT(11) PRIMARY KEY AUTO_INCREMENT COMMENT '記錄ID',
    student_cookie VARCHAR(100) NOT NULL COMMENT '學生Cookie',
    classroom_id INT(11) NOT NULL COMMENT '教室ID',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '加入時間',
    INDEX idx_student_cookie (student_cookie),
    INDEX idx_classroom_id (classroom_id),
    UNIQUE KEY unique_student_classroom (student_cookie, classroom_id),
    FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='學生教室訪問記錄表';

-- 添加外鍵約束（如果不存在）
-- 注意：如果表中已有不符合外鍵約束的數據，這個操作可能會失敗
-- 在生產環境中，可能需要先清理數據
SET foreign_key_checks = 0;
ALTER TABLE assignments 
ADD CONSTRAINT fk_assignments_classroom 
FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE SET NULL;
SET foreign_key_checks = 1;

-- 插入默認配置數據
INSERT INTO config (config_key, config_value) VALUES 
('school_name', '臺北市幼華高級中學'),
('platform_title', '網站實作作業平台'),
('admin_username', 'admin'),
('admin_password', 'admin123456'),
('max_score', '100'),
('score_visibility', 'private'),
('allow_score_comments', '1'),
('enable_featured', '1'),
('default_classroom', '')
ON DUPLICATE KEY UPDATE 
config_value = VALUES(config_value),
updated_at = CURRENT_TIMESTAMP;

-- 插入默認教室
INSERT INTO classrooms (name, description, share_code, is_active, require_password, password) VALUES 
('預設教室', '系統預設教室，用於展示作業功能', 'DEFAULT2025', 1, 0, NULL)
ON DUPLICATE KEY UPDATE 
name = VALUES(name),
description = VALUES(description),
updated_at = CURRENT_TIMESTAMP;

-- 顯示初始化結果
SELECT 'Database initialization completed!' as Status;
SELECT 'Tables created:' as Info;
SHOW TABLES;

SELECT 'Configuration data:' as Info;
SELECT config_key, config_value FROM config ORDER BY config_key;

SELECT 'Default classroom:' as Info;
SELECT id, name, share_code, is_active FROM classrooms WHERE share_code = 'DEFAULT2025';
