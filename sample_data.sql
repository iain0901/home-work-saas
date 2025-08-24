-- =====================================================
-- 臺北市幼華高級中學 - 示例數據
-- 可選的示例數據插入腳本
-- =====================================================
-- 使用方法：
-- mysql -u workt -p4P7wf3n8inXCp4pZ workt < sample_data.sql
-- =====================================================

-- 設置字符集
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- 插入示例作業數據
INSERT INTO assignments (id, group_name, student_name, title, url, submitter_cookie, submit_time, edit_time) VALUES 
('sample001', '第一組', '張小明', '我的第一個網站', 'https://example1.com', 'cookie001', '2025-08-20 10:00:00', NULL),
('sample002', '第二組', '李小華', 'CSS動畫練習', 'https://example2.com', 'cookie002', '2025-08-20 11:00:00', '2025-08-20 12:00:00'),
('sample003', '第三組', '王小美', 'JavaScript互動頁面', 'https://example3.com', 'cookie003', '2025-08-20 12:00:00', NULL),
('sample004', '第四組', '陳小強', 'RWD響應式設計', 'https://example4.com', 'cookie004', '2025-08-20 13:00:00', NULL),
('sample005', '第五組', '林小雅', 'Bootstrap框架應用', 'https://example5.com', 'cookie005', '2025-08-20 14:00:00', '2025-08-20 15:00:00')
ON DUPLICATE KEY UPDATE 
group_name = VALUES(group_name),
student_name = VALUES(student_name),
title = VALUES(title),
url = VALUES(url),
updated_at = CURRENT_TIMESTAMP;

-- 顯示插入結果
SELECT 'Sample data inserted successfully!' as Status;
SELECT 'Total assignments:' as Info;
SELECT COUNT(*) as count FROM assignments;

SELECT 'Sample assignments:' as Info;
SELECT id, group_name, student_name, title, submit_time 
FROM assignments 
WHERE id LIKE 'sample%' 
ORDER BY submit_time;
