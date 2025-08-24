-- =====================================================
-- 臺北市幼華高級中學 - 數據庫清理腳本
-- 清理所有數據但保留表結構
-- =====================================================
-- 使用方法：
-- mysql -u workt -p4P7wf3n8inXCp4pZ workt < cleanup_database.sql
-- =====================================================

-- 設置字符集
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- 備份當前數據計數
SELECT 'Before cleanup:' as Status;
SELECT 'Assignments count:' as Info, COUNT(*) as count FROM assignments;
SELECT 'Config count:' as Info, COUNT(*) as count FROM config;

-- 清空作業表（保留表結構）
TRUNCATE TABLE assignments;

-- 重置配置為默認值
DELETE FROM config;
INSERT INTO config (config_key, config_value) VALUES 
('school_name', '臺北市幼華高級中學'),
('platform_title', '網站實作作業平台'),
('admin_username', 'admin'),
('admin_password', 'admin123456');

-- 顯示清理結果
SELECT 'Database cleanup completed!' as Status;
SELECT 'After cleanup:' as Info;
SELECT 'Assignments count:' as Info, COUNT(*) as count FROM assignments;
SELECT 'Config count:' as Info, COUNT(*) as count FROM config;

SELECT 'Current configuration:' as Info;
SELECT config_key, config_value FROM config ORDER BY config_key;
