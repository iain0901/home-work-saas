<?php
require_once 'db_config.php';

echo "<h2>數據庫初始化</h2>";

try {
    // 初始化數據庫表
    initDatabase();
    echo "✅ 數據庫表創建成功<br>";
    
    // 檢查是否有JSON數據需要遷移
    $json_file = 'assignments.json';
    if (file_exists($json_file)) {
        $json_data = json_decode(file_get_contents($json_file), true);
        if (!empty($json_data)) {
            echo "<h3>遷移JSON數據到MySQL</h3>";
            
            $db = getDB();
            $stmt = $db->prepare("INSERT INTO assignments (id, group_name, student_name, title, url, submitter_cookie, submit_time, edit_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            $migrated = 0;
            foreach ($json_data as $assignment) {
                try {
                    $stmt->execute([
                        $assignment['id'],
                        $assignment['group'],
                        $assignment['name'],
                        $assignment['title'],
                        $assignment['url'],
                        $assignment['submitter_cookie'],
                        $assignment['submit_time'],
                        $assignment['edit_time'] ?? null
                    ]);
                    $migrated++;
                } catch (PDOException $e) {
                    // 如果記錄已存在，跳過
                    if ($e->getCode() != '23000') {
                        echo "❌ 遷移記錄失敗: " . $e->getMessage() . "<br>";
                    }
                }
            }
            
            echo "✅ 成功遷移 {$migrated} 筆記錄<br>";
            
            // 備份JSON文件
            $backup_name = 'assignments_backup_' . date('Y-m-d_H-i-s') . '.json';
            copy($json_file, $backup_name);
            echo "✅ JSON文件已備份為: {$backup_name}<br>";
        }
    }
    
    // 檢查配置文件並遷移
    $config_file = 'config.json';
    if (file_exists($config_file)) {
        $config_data = json_decode(file_get_contents($config_file), true);
        if (!empty($config_data)) {
            echo "<h3>遷移配置數據到MySQL</h3>";
            
            $db = getDB();
            $stmt = $db->prepare("INSERT INTO config (config_key, config_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)");
            
            foreach ($config_data as $key => $value) {
                $stmt->execute([$key, $value]);
            }
            
            echo "✅ 配置數據遷移成功<br>";
            
            // 備份配置文件
            $backup_name = 'config_backup_' . date('Y-m-d_H-i-s') . '.json';
            copy($config_file, $backup_name);
            echo "✅ 配置文件已備份為: {$backup_name}<br>";
        }
    }
    
    echo "<h3>數據庫狀態檢查</h3>";
    
    // 檢查作業數量
    $db = getDB();
    $stmt = $db->query("SELECT COUNT(*) as count FROM assignments");
    $assignment_count = $stmt->fetch()['count'];
    echo "📚 作業總數: {$assignment_count}<br>";
    
    // 檢查配置數量
    $stmt = $db->query("SELECT COUNT(*) as count FROM config");
    $config_count = $stmt->fetch()['count'];
    echo "⚙️ 配置項目數: {$config_count}<br>";
    
    echo "<h3>✅ 數據庫初始化完成！</h3>";
    echo "<p><a href='index.php'>返回首頁</a> | <a href='admin_login.php'>管理登入</a></p>";
    
} catch (Exception $e) {
    echo "❌ 初始化失敗: " . $e->getMessage();
}
?>
