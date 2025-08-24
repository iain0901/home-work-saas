# MySQL數據庫初始化指南

## 📋 概述

此文檔提供了在其他環境中初始化MySQL數據庫的完整指南。

## 🗂️ SQL文件說明

### 1. `deploy.sql` - 完整部署腳本（需要root權限）
- 創建數據庫和用戶
- 創建所有表結構
- 插入默認配置
- 設置權限

```bash
# 使用root用戶執行（首次部署）
mysql -u root -p < deploy.sql
```

### 2. `init_database.sql` - 數據庫初始化（使用workt用戶）
- 創建表結構
- 插入默認配置
- 創建索引

```bash
# 使用workt用戶執行
mysql -u workt -p4P7wf3n8inXCp4pZ work < init_database.sql
```

### 3. `sample_data.sql` - 示例數據（可選）
- 插入示例作業數據
- 用於測試和演示

```bash
# 插入示例數據
mysql -u workt -p4P7wf3n8inXCp4pZ work < sample_data.sql
```

### 4. `cleanup_database.sql` - 數據清理
- 清空所有作業數據
- 重置配置為默認值
- 保留表結構

```bash
# 清理數據庫
mysql -u workt -p4P7wf3n8inXCp4pZ work < cleanup_database.sql
```

## 🚀 部署步驟

### 方案一：完整新環境部署（推薦）

```bash
# 1. 使用root權限完整部署
mysql -u root -p < deploy.sql

# 2. 驗證部署
mysql -u workt -p4P7wf3n8inXCp4pZ work -e "SHOW TABLES;"

# 3. （可選）插入示例數據
mysql -u workt -p4P7wf3n8inXCp4pZ work < sample_data.sql
```

### 方案二：workt用戶已存在

```bash
# 1. 初始化數據庫結構
mysql -u workt -p4P7wf3n8inXCp4pZ work < init_database.sql

# 2. 驗證初始化
mysql -u workt -p4P7wf3n8inXCp4pZ work -e "SELECT COUNT(*) FROM config;"
```

### 方案三：數據遷移

如果您有現有的JSON數據需要遷移：

```bash
# 1. 初始化數據庫
mysql -u workt -p4P7wf3n8inXCp4pZ work < init_database.sql

# 2. 使用PHP腳本遷移數據
php init_db.php
```

## 🔧 數據庫配置

### 連接參數
```
主機: localhost
數據庫: workt
用戶名: workt
密碼: 4P7wf3n8inXCp4pZ
字符集: utf8mb4
```

### 表結構

#### assignments表（作業表）
```sql
- id: VARCHAR(50) PRIMARY KEY
- group_name: VARCHAR(100) NOT NULL
- student_name: VARCHAR(100) NOT NULL  
- title: VARCHAR(200) NOT NULL
- url: VARCHAR(500) NOT NULL
- submitter_cookie: VARCHAR(100) NOT NULL
- submit_time: DATETIME NOT NULL
- edit_time: DATETIME NULL
- created_at: TIMESTAMP DEFAULT CURRENT_TIMESTAMP
- updated_at: TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

#### config表（配置表）
```sql
- config_key: VARCHAR(100) PRIMARY KEY
- config_value: TEXT NOT NULL
- created_at: TIMESTAMP DEFAULT CURRENT_TIMESTAMP
- updated_at: TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

## 🧪 測試驗證

### 驗證數據庫連接
```bash
mysql -u workt -p4P7wf3n8inXCp4pZ work -e "SELECT 1;"
```

### 檢查表結構
```bash
mysql -u workt -p4P7wf3n8inXCp4pZ work -e "DESCRIBE assignments; DESCRIBE config;"
```

### 檢查配置數據
```bash
mysql -u workt -p4P7wf3n8inXCp4pZ work -e "SELECT * FROM config;"
```

### 檢查作業數據
```bash
mysql -u workt -p4P7wf3n8inXCp4pZ work -e "SELECT COUNT(*) as total FROM assignments;"
```

## 🛠️ 常見問題

### 1. 權限錯誤
```bash
# 確保workt用戶有正確權限
mysql -u root -p -e "GRANT ALL PRIVILEGES ON workt.* TO 'workt'@'localhost'; FLUSH PRIVILEGES;"
```

### 2. 字符集問題
```bash
# 確保數據庫使用utf8mb4字符集
mysql -u root -p -e "ALTER DATABASE workt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 3. 重新初始化
```bash
# 完全重新開始
mysql -u workt -p4P7wf3n8inXCp4pZ work < cleanup_database.sql
mysql -u workt -p4P7wf3n8inXCp4pZ work < init_database.sql
```

## 📱 Web應用配置

確保以下PHP文件存在並配置正確：
- `db_config.php` - 數據庫連接配置
- `config_helper.php` - 配置助手函數
- `index.php` - 主頁面
- `upload.php` - 上傳頁面
- `admin.php` - 管理面板
- `admin_login.php` - 管理員登入

## 🔐 默認帳號

### 管理員帳號
- 用戶名: `admin`
- 密碼: `admin123456`

### 測試帳號
- 帳號: `iain@100thy.com`
- 密碼: `iain@100thy.com`

## 📞 技術支援

如有問題，請檢查：
1. MySQL服務是否運行
2. work用戶權限是否正確
3. 字符集是否為utf8mb4
4. PHP PDO擴展是否已安裝
