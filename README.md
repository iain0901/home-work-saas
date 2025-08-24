# 🎓 作業管理系統 (Assignment Management System)

一個功能完整的學校作業管理系統，支持教室管理、作業提交、評分系統、抽獎功能和數據導出。

⚠️本專案是Vibe Coding但有做基本的數據安全防護及測試了⚠️

## ✨ 主要功能

### 🏫 教室管理 (這邊的教室也可以當作每個單元的作業分類來用)
- **多教室支持**：創建和管理多個教室
- **分享機制**：生成教室分享代碼和QR碼
- **密碼保護**：可選的教室密碼保護功能
- **一鍵分享**：複製分享連結和下載QR碼

### 📝 作業管理
- **作業提交**：學生可提交作業標題和網址
- **評分系統**：教師可為作業評分並添加評語
- **公開控制**：靈活的作業公開/私有設置
- **精選功能**：標記優秀作業為精選
- **批量操作**：支持批量設置公開狀態和刪除

### 🎲 抽獎功能
- **多種名單來源**：
  - 從教室組別導入
  - 手動創建名單
  - 數字範圍定義
- **豐富動畫**：3D轉盤、彩帶、煙花特效
- **音效支持**：旋轉和勝利音效
- **重複控制**：可設置是否允許重複抽中
- **歷史記錄**：保存抽獎歷史

### 📊 數據導出
- **CSV導出**：完整的作業數據導出
- **靈活篩選**：按教室、日期、狀態篩選
- **多種排序**：支持8種排序方式
- **批量選擇**：可導出選中的特定作業

## 🛠️ 技術棧

- **後端**：PHP 7.4+
- **數據庫**：MySQL 5.7+
- **前端**：HTML5, CSS3, JavaScript (ES6+)
- **UI框架**：自定義響應式設計
- **特效**：CSS動畫 + Web Audio API

## 📋 系統要求

### 服務器要求
- PHP 7.4 或更高版本
- MySQL 5.7 或更高版本
- Apache/Nginx 網頁服務器
- 支持 PDO MySQL 擴展

### 瀏覽器支持
- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## 🚀 安裝部署

### 1. 克隆項目
```bash
git clone https://github.com/your-username/assignment-management-system.git
cd assignment-management-system
```

### 2. 數據庫配置
```bash
# 使用提供的SQL腳本創建數據庫
mysql -u root -p < deploy.sql

# 或者手動執行以下步驟：
mysql -u root -p
CREATE DATABASE workt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'workt'@'localhost' IDENTIFIED BY '4P7wf3n8inXCp4pZ';
GRANT ALL PRIVILEGES ON workt.* TO 'workt'@'localhost';
FLUSH PRIVILEGES;

# 初始化數據庫表結構
mysql -u workt -p4P7wf3n8inXCp4pZ workt < init_database.sql

# 載入測試數據（可選）
mysql -u workt -p4P7wf3n8inXCp4pZ workt < sample_data.sql
```

### 3. 配置文件
確保 `db_config.php` 中的數據庫配置正確：
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'workt');
define('DB_USER', 'workt');
define('DB_PASS', '4P7wf3n8inXCp4pZ');
```

### 4. 文件權限
```bash
# 設置適當的文件權限
chmod 755 /path/to/your/project
chmod 644 /path/to/your/project/*.php
```

### 5. 訪問系統
- **學生端**：`http://your-domain.com/`
- **管理端**：`http://your-domain.com/admin_login.php`
- **默認管理員密碼**：請查看 `config` 表中的設置

## 📁 項目結構

```
assignment-management-system/
├── 📄 核心文件
│   ├── index.php                    # 教室選擇首頁
│   ├── classroom.php                # 教室作業展示頁
│   ├── upload.php                   # 作業提交頁面
│   └── lottery.php                  # 抽獎功能頁面
│
├── 🔧 管理功能
│   ├── admin_login.php              # 管理員登入
│   ├── admin.php                    # 管理面板首頁
│   ├── admin_assignments.php        # 作業管理
│   ├── admin_classrooms.php         # 教室管理
│   └── admin_settings.php           # 系統設置
│
├── 📊 數據處理
│   ├── export_assignments_csv.php   # CSV導出功能
│   └── qr_generator.php             # QR碼生成
│
├── ⚙️ 配置文件
│   ├── db_config.php                # 數據庫配置
│   └── config_helper.php            # 配置助手
│
├── 🎨 樣式文件
│   └── checkbox-styles.css          # 自定義UI樣式
│
├── 📚 文檔
│   ├── USER_MANUAL.md               # 用戶手冊
│   ├── TEACHER_GUIDE.md             # 教師指南
│   ├── STUDENT_GUIDE.md             # 學生指南
│   └── help.php                     # 在線幫助
│
└── 🗄️ 數據庫
    ├── deploy.sql                   # 完整部署腳本
    ├── init_database.sql            # 數據庫初始化
    ├── sample_data.sql              # 測試數據
    └── cleanup_database.sql         # 數據清理
```

## 🎯 快速開始

### 學生使用流程
1. 訪問首頁選擇教室
2. 輸入教室密碼（如需要）
3. 查看其他同學的作業
4. 點擊"提交作業"上傳自己的作業

### 教師使用流程
1. 登入管理後台
2. 創建教室並設置密碼
3. 分享教室代碼或QR碼給學生
4. 管理和評分學生作業
5. 使用抽獎功能進行課堂互動

## 🔧 配置選項

### 系統設置
- **學校名稱**：自定義學校/機構名稱
- **最高分數**：設置評分系統的最高分數
- **管理員密碼**：修改管理員登入密碼

### 教室設置
- **教室名稱**：為每個教室設置名稱
- **密碼保護**：可選的教室訪問密碼
- **分享代碼**：自動生成的教室分享代碼

## 🎨 功能特色

### 🎡 抽獎轉盤
- **3D視覺效果**：立體轉盤設計
- **豐富動畫**：光暈、彩帶、煙花
- **音效支持**：Web Audio API音效
- **靈活設置**：多種名單來源和重複控制

### 📱 響應式設計
- **移動優化**：完美支持手機和平板
- **自適應佈局**：根據屏幕大小調整
- **觸摸友好**：優化的觸摸操作體驗

### 🎨 現代UI
- **漸變色彩**：美觀的色彩搭配
- **平滑動畫**：CSS3過渡效果
- **直觀操作**：用戶友好的界面設計

## 📊 數據管理

### CSV導出功能
- **完整字段**：14個字段的詳細信息
- **靈活篩選**：多種篩選條件
- **Excel兼容**：UTF-8 BOM支持
- **批量操作**：支持批量選擇導出

### 數據備份
```bash
# 備份數據庫
mysqldump -u workt -p4P7wf3n8inXCp4pZ workt > backup_$(date +%Y%m%d).sql

# 還原數據庫
mysql -u workt -p4P7wf3n8inXCp4pZ workt < backup_20250101.sql
```

## 🔒 安全特性

- **Session管理**：安全的用戶會話控制
- **SQL注入防護**：使用PDO準備語句
- **權限驗證**：管理功能需要登入驗證
- **數據驗證**：所有輸入數據都經過驗證

## 🐛 故障排除

### 常見問題

**Q: 無法連接數據庫**
```
A: 檢查 db_config.php 中的數據庫配置是否正確
```

**Q: 管理員無法登入**
```
A: 檢查 config 表中的 admin_password 設置
```

**Q: CSV導出亂碼**
```
A: 確保數據庫和文件都使用 UTF-8 編碼
```

**Q: 抽獎動畫不顯示**
```
A: 檢查瀏覽器是否支持 CSS3 和 Web Audio API
```

## 🤝 貢獻指南

1. Fork 本項目
2. 創建功能分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 創建 Pull Request

## 📝 更新日誌

### v2.0.0 (2025-01-20)
- ✨ 新增抽獎功能和豐富動畫效果
- 📊 添加CSV導出功能
- 🏫 實現多教室管理系統
- 🔒 添加教室密碼保護
- 📱 優化響應式設計
- 🎨 改進UI/UX設計

### v1.0.0 (2025-01-01)
- 🎉 初始版本發布
- 📝 基本作業管理功能
- 👨‍🏫 管理員評分系統
- 🌐 響應式網頁設計

## 📄 許可證

本項目採用 MIT 許可證 - 查看 [LICENSE](LICENSE) 文件了解詳情。

## 👥 作者

- **開發者** - *初始工作* - [YourGitHub](https://github.com/yourusername)

## 🙏 致謝

- 感謝所有測試用戶的反饋
- 感謝開源社區的支持
- 特別感謝教育工作者的建議

## 📞 支持

如果您遇到任何問題或有功能建議，請：

- 📧 發送郵件至：iain@100thy.com
- 🐛 在GitHub上創建Issue

---

⭐ 如果這個項目對您有幫助，請給我們一個星標！

🔗 **相關鏈接**
- [在線演示](http://work.100thy.com)
