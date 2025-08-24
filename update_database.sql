-- =====================================================
-- 數據庫更新腳本 (不需要root權限)
-- 用於更新現有數據庫到最新結構
-- =====================================================
-- 使用方法：
-- mysql -u workt -p4P7wf3n8inXCp4pZ workt < update_database.sql
-- =====================================================

-- 設置字符集
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- 使用workt數據庫
USE workt;

-- 檢查並添加assignments表的新字段（如果不存在）
ALTER TABLE assignments 
ADD COLUMN IF NOT EXISTS classroom_id INT(11) NULL COMMENT '教室ID' AFTER updated_at,
ADD COLUMN IF NOT EXISTS score DECIMAL(5,2) NULL COMMENT '作業分數' AFTER classroom_id,
ADD COLUMN IF NOT EXISTS score_comment TEXT NULL COMMENT '評分備註' AFTER score,
ADD COLUMN IF NOT EXISTS showcase_status ENUM('pending','approved','rejected','auto') DEFAULT 'pending' COMMENT '展示狀態' AFTER score_comment,
ADD COLUMN IF NOT EXISTS is_featured TINYINT(1) DEFAULT 0 COMMENT '是否精選' AFTER showcase_status,
ADD COLUMN IF NOT EXISTS is_public TINYINT(1) DEFAULT 0 COMMENT '是否公開' AFTER is_featured;

-- 添加索引（如果不存在）
CREATE INDEX IF NOT EXISTS idx_assignments_classroom_id ON assignments(classroom_id);
CREATE INDEX IF NOT EXISTS idx_assignments_score ON assignments(score);
CREATE INDEX IF NOT EXISTS idx_assignments_showcase_status ON assignments(showcase_status);
CREATE INDEX IF NOT EXISTS idx_assignments_is_featured ON assignments(is_featured);

-- 創建classrooms表（如果不存在）
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

-- 創建student_classroom_access表（如果不存在）
CREATE TABLE IF NOT EXISTS student_classroom_access (
    id INT(11) PRIMARY KEY AUTO_INCREMENT COMMENT '記錄ID',
    student_cookie VARCHAR(100) NOT NULL COMMENT '學生Cookie',
    classroom_id INT(11) NOT NULL COMMENT '教室ID',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '加入時間',
    INDEX idx_student_cookie (student_cookie),
    INDEX idx_classroom_id (classroom_id),
    UNIQUE KEY unique_student_classroom (student_cookie, classroom_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='學生教室訪問記錄表';

-- 插入或更新配置數據
INSERT INTO config (config_key, config_value) VALUES 
('max_score', '100'),
('score_visibility', 'private'),
('allow_score_comments', '1'),
('enable_featured', '1'),
('default_classroom', '')
ON DUPLICATE KEY UPDATE 
config_value = VALUES(config_value),
updated_at = CURRENT_TIMESTAMP;

-- 插入默認教室（如果不存在）
INSERT IGNORE INTO classrooms (name, description, share_code, is_active, require_password, password) VALUES 
('預設教室', '系統預設教室，用於展示作業功能', 'DEFAULT2025', 1, 0, NULL);

-- 顯示更新結果
SELECT 'Database update completed!' as Status;
SELECT 'Current tables:' as Info;
SHOW TABLES;

SELECT 'Current config count:' as Info, COUNT(*) as count FROM config;
SELECT 'Current classroom count:' as Info, COUNT(*) as count FROM classrooms;

-- 顯示assignments表結構
SELECT 'Assignments table columns:' as Info;
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'workt' AND TABLE_NAME = 'assignments' 
ORDER BY ORDINAL_POSITION;
