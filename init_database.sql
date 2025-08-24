-- =====================================================
-- 臺北市幼華高級中學 - 網站實作作業平台
-- MySQL數據庫初始化腳本
-- =====================================================
-- 使用方法：
-- mysql -u workt -p4P7wf3n8inXCp4pZ workt < init_database.sql
-- =====================================================

-- 設置字符集
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- 創建assignments表（作業表）
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='作業提交表';

-- 創建config表（配置表）
CREATE TABLE IF NOT EXISTS config (
    config_key VARCHAR(100) PRIMARY KEY COMMENT '配置鍵',
    config_value TEXT NOT NULL COMMENT '配置值',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '創建時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系統配置表';

-- 插入默認配置數據
INSERT INTO config (config_key, config_value) VALUES 
('school_name', '臺北市幼華高級中學'),
('platform_title', '網站實作作業平台'),
('admin_username', 'admin'),
('admin_password', 'admin123456')
ON DUPLICATE KEY UPDATE 
config_value = VALUES(config_value),
updated_at = CURRENT_TIMESTAMP;

-- 創建索引以提高查詢性能
CREATE INDEX idx_assignments_submit_time ON assignments(submit_time);
CREATE INDEX idx_assignments_group_name ON assignments(group_name);
CREATE INDEX idx_assignments_submitter_cookie ON assignments(submitter_cookie);

-- 顯示創建結果
SELECT 'Database initialization completed!' as Status;
SELECT 'Tables created:' as Info;
SHOW TABLES;

SELECT 'Configuration data:' as Info;
SELECT config_key, config_value FROM config ORDER BY config_key;

SELECT 'Assignments count:' as Info;
SELECT COUNT(*) as total_assignments FROM assignments;
