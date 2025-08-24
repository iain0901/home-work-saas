# 🚀 部署說明 (Deployment Guide)

## 📋 快速部署檢查清單

### ✅ 準備工作
- [ ] PHP 7.4+ 環境
- [ ] MySQL 5.7+ 數據庫
- [ ] Web服務器 (Apache/Nginx)
- [ ] Git (用於代碼管理)

### ✅ 部署步驟

#### 1. 下載代碼
```bash
# 克隆或下載項目代碼
git clone https://github.com/your-username/assignment-management-system.git
cd assignment-management-system
```

#### 2. 數據庫設置
```bash
# 方法一：使用完整部署腳本（推薦）
mysql -u root -p < deploy.sql

# 方法二：手動設置
mysql -u root -p
```
```sql
CREATE DATABASE workt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'workt'@'localhost' IDENTIFIED BY '4P7wf3n8inXCp4pZ';
GRANT ALL PRIVILEGES ON workt.* TO 'workt'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```
```bash
# 初始化表結構
mysql -u workt -p4P7wf3n8inXCp4pZ workt < init_database.sql

# 載入測試數據
mysql -u workt -p4P7wf3n8inXCp4pZ workt < sample_data.sql
```

#### 3. 配置檢查
確保以下文件配置正確：

**db_config.php**
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'workt');
define('DB_USER', 'workt');
define('DB_PASS', '4P7wf3n8inXCp4pZ');
```

#### 4. 文件權限
```bash
chmod 755 /path/to/project
chmod 644 /path/to/project/*.php
```

#### 5. 測試訪問
- 學生端：`http://your-domain.com/`
- 管理端：`http://your-domain.com/admin_login.php`

## 🔧 環境配置

### Apache配置
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/assignment-management-system
    
    <Directory /path/to/assignment-management-system>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Nginx配置
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/assignment-management-system;
    index index.php index.html;
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## 📦 Docker部署（可選）

### Dockerfile
```dockerfile
FROM php:7.4-apache

# 安裝PHP擴展
RUN docker-php-ext-install pdo pdo_mysql

# 複製代碼
COPY . /var/www/html/

# 設置權限
RUN chown -R www-data:www-data /var/www/html
```

### docker-compose.yml
```yaml
version: '3.8'
services:
  web:
    build: .
    ports:
      - "80:80"
    depends_on:
      - db
    volumes:
      - .:/var/www/html
      
  db:
    image: mysql:5.7
    environment:
      MYSQL_DATABASE: workt
      MYSQL_USER: workt
      MYSQL_PASSWORD: 4P7wf3n8inXCp4pZ
      MYSQL_ROOT_PASSWORD: rootpassword
    volumes:
      - db_data:/var/lib/mysql
      - ./init_database.sql:/docker-entrypoint-initdb.d/init.sql

volumes:
  db_data:
```

## 🔒 安全配置

### 1. 修改默認密碼
```sql
UPDATE config SET config_value = 'your-new-password' WHERE config_key = 'admin_password';
```

### 2. 數據庫安全
```sql
# 修改數據庫用戶密碼
ALTER USER 'workt'@'localhost' IDENTIFIED BY 'your-new-db-password';
```

### 3. 文件安全
```bash
# 隱藏敏感文件
echo "deny from all" > /path/to/project/.htaccess
```

## 📊 性能優化

### PHP配置優化
```ini
# php.ini
memory_limit = 256M
max_execution_time = 300
upload_max_filesize = 10M
post_max_size = 10M
```

### MySQL配置優化
```ini
# my.cnf
[mysqld]
innodb_buffer_pool_size = 256M
query_cache_size = 64M
max_connections = 100
```

## 🔄 更新流程

### 1. 備份數據
```bash
# 備份數據庫
mysqldump -u workt -p4P7wf3n8inXCp4pZ workt > backup_$(date +%Y%m%d).sql

# 備份文件
tar -czf backup_files_$(date +%Y%m%d).tar.gz /path/to/project
```

### 2. 更新代碼
```bash
git pull origin main
# 或下載新版本代碼覆蓋
```

### 3. 數據庫遷移
```bash
# 如有數據庫結構變更，執行遷移腳本
mysql -u workt -p4P7wf3n8inXCp4pZ workt < migration.sql
```

## 🐛 故障排除

### 常見錯誤

**500 Internal Server Error**
```bash
# 檢查Apache錯誤日誌
tail -f /var/log/apache2/error.log

# 檢查PHP錯誤
tail -f /var/log/php_errors.log
```

**數據庫連接失敗**
```bash
# 測試數據庫連接
mysql -u workt -p4P7wf3n8inXCp4pZ -h localhost workt
```

**權限問題**
```bash
# 修復文件權限
chown -R www-data:www-data /path/to/project
chmod -R 755 /path/to/project
```

## 📱 移動端配置

### PWA支持（可選）
創建 `manifest.json`：
```json
{
  "name": "作業管理系統",
  "short_name": "作業系統",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#007bff",
  "icons": [
    {
      "src": "icon-192.png",
      "sizes": "192x192",
      "type": "image/png"
    }
  ]
}
```

## 📈 監控配置

### 基本監控
```bash
# 設置cron監控
crontab -e
```
```cron
# 每5分鐘檢查服務狀態
*/5 * * * * curl -f http://your-domain.com/ || echo "Site down" | mail admin@domain.com
```

### 日誌輪轉
```bash
# /etc/logrotate.d/assignment-system
/var/log/assignment-system/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
```

## ✅ 部署檢查

部署完成後，請檢查以下項目：

- [ ] 首頁正常顯示
- [ ] 管理員可以登入
- [ ] 學生可以提交作業
- [ ] 教室功能正常
- [ ] 抽獎功能正常
- [ ] CSV導出功能正常
- [ ] 手機端顯示正常
- [ ] 數據庫連接正常
- [ ] 文件權限正確

## 📞 技術支持

如遇到部署問題，請：
1. 檢查錯誤日誌
2. 確認系統要求
3. 查看故障排除指南
4. 聯繫技術支持

---

🎉 **恭喜！您已成功部署作業管理系統！**
