<?php
// 配置助手函數 - MySQL版本
require_once 'db_config.php';

function get_config() {
    static $config_cache = null;
    
    if ($config_cache !== null) {
        return $config_cache;
    }
    
    $default_config = [
        'school_name' => '臺北市幼華高級中學',
        'platform_title' => '網站實作作業平台',
        'admin_username' => 'admin',
        'admin_password' => 'admin123456'
    ];
    
    try {
        $db = getDB();
        $stmt = $db->query("SELECT config_key, config_value FROM config");
        $db_config = [];
        
        while ($row = $stmt->fetch()) {
            $db_config[$row['config_key']] = $row['config_value'];
        }
        
        $config_cache = array_merge($default_config, $db_config);
        return $config_cache;
        
    } catch (Exception $e) {
        // 如果數據庫連接失敗，返回默認配置
        return $default_config;
    }
}

function get_school_name() {
    $config = get_config();
    return $config['school_name'];
}

function get_platform_title() {
    $config = get_config();
    return $config['platform_title'];
}

function update_config($key, $value) {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO config (config_key, config_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)");
        $stmt->execute([$key, $value]);
        
        // 清除緩存
        global $config_cache;
        $config_cache = null;
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
