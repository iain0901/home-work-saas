<?php
// 載入配置助手
require_once 'config_helper.php';
$config = get_config();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>使用說明 - <?php echo htmlspecialchars($config['school_name'] ?? '學校'); ?></title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .help-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .help-tabs {
            display: flex;
            background: white;
            border-radius: 12px 12px 0 0;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 0;
        }
        
        .help-tab {
            flex: 1;
            padding: 20px;
            background: #f8f9fa;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s ease;
            color: #6c757d;
        }
        
        .help-tab.active {
            background: #007bff;
            color: white;
        }
        
        .help-tab:hover:not(.active) {
            background: #e9ecef;
        }
        
        .help-content {
            background: white;
            padding: 40px;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            min-height: 600px;
        }
        
        .help-section {
            display: none;
        }
        
        .help-section.active {
            display: block;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin: 30px 0;
        }
        
        .feature-card {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            border-left: 5px solid #007bff;
        }
        
        .feature-icon {
            font-size: 2.5em;
            margin-bottom: 15px;
        }
        
        .feature-title {
            font-size: 1.3em;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .feature-description {
            color: #6c757d;
            line-height: 1.6;
        }
        
        .step-list {
            counter-reset: step-counter;
            list-style: none;
            padding: 0;
        }
        
        .step-list li {
            counter-increment: step-counter;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            position: relative;
            padding-left: 60px;
        }
        
        .step-list li::before {
            content: counter(step-counter);
            position: absolute;
            left: 20px;
            top: 20px;
            background: #007bff;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .code-block {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            border-left: 4px solid #007bff;
            margin: 15px 0;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #17a2b8;
            margin: 15px 0;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #ffc107;
            margin: 15px 0;
        }
        
        .btn-demo {
            display: inline-block;
            padding: 10px 20px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 10px 10px 0;
            transition: background 0.3s ease;
        }
        
        .btn-demo:hover {
            background: #218838;
            color: white;
        }
        
        @media (max-width: 768px) {
            .help-tabs {
                flex-direction: column;
            }
            
            .help-content {
                padding: 20px;
            }
            
            .feature-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><?php echo htmlspecialchars($config['school_name'] ?? '學校'); ?></h1>
            <h2>📖 使用說明</h2>
            <div class="header-actions">
                <a href="index.php" class="btn-view">返回首頁</a>
            </div>
        </header>

        <div class="help-container">
            <div class="help-tabs">
                <button class="help-tab active" onclick="showSection('student')">👨‍🎓 學生使用</button>
                <button class="help-tab" onclick="showSection('teacher')">👩‍🏫 教師使用</button>
                <button class="help-tab" onclick="showSection('features')">✨ 功能介紹</button>
            </div>

            <div class="help-content">
                <!-- 學生使用說明 -->
                <div id="student" class="help-section active">
                    <h2>👨‍🎓 學生使用指南</h2>
                    
                    <h3>📝 如何上傳作業</h3>
                    <ol class="step-list">
                        <li>
                            <strong>進入上傳頁面</strong><br>
                            點擊首頁的「新增作業」按鈕，或直接前往上傳頁面
                            <a href="upload.php" class="btn-demo">立即上傳作業</a>
                        </li>
                        <li>
                            <strong>填寫作業資訊</strong><br>
                            • 組別：例如「第一組」、「第二組」<br>
                            • 姓名：您的真實姓名<br>
                            • 網站標題：作業的標題<br>
                            • 網站網址：完整的網址（必須包含 http:// 或 https://）
                        </li>
                        <li>
                            <strong>選擇教室（可選）</strong><br>
                            如果老師有提供教室代碼，可以選擇對應的教室
                        </li>
                        <li>
                            <strong>提交作業</strong><br>
                            點擊「上傳作業」按鈕完成提交
                        </li>
                    </ol>

                    <div class="alert-info">
                        <strong>💡 小提示：</strong>如果老師提供了教室分享連結，直接點擊連結會自動選擇教室！
                    </div>

                    <h3>🔗 支援的網站平台</h3>
                    <div class="feature-grid">
                        <div class="feature-card">
                            <div class="feature-icon">🐙</div>
                            <div class="feature-title">GitHub Pages</div>
                            <div class="feature-description">免費的靜態網站託管，支援自訂網域</div>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">🚀</div>
                            <div class="feature-title">Netlify / Vercel</div>
                            <div class="feature-description">現代化的網站部署平台</div>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">✏️</div>
                            <div class="feature-title">CodePen / JSFiddle</div>
                            <div class="feature-description">線上程式碼編輯器，適合小型專案</div>
                        </div>
                    </div>

                    <h3>✏️ 如何編輯已上傳的作業</h3>
                    <ol class="step-list">
                        <li>
                            <strong>找到您的作業</strong><br>
                            在首頁找到您上傳的作業卡片（只有您自己的作業會顯示編輯按鈕）
                        </li>
                        <li>
                            <strong>點擊編輯</strong><br>
                            點擊作業卡片上的「編輯」按鈕
                        </li>
                        <li>
                            <strong>修改資訊</strong><br>
                            在彈出的視窗中修改作業資訊
                        </li>
                        <li>
                            <strong>儲存修改</strong><br>
                            點擊「更新作業」儲存修改
                        </li>
                    </ol>

                    <div class="alert-warning">
                        <strong>⚠️ 注意事項：</strong><br>
                        • 網址必須是完整的，包含 http:// 或 https://<br>
                        • 刪除作業後無法復原，請謹慎操作<br>
                        • 只能編輯和刪除自己上傳的作業
                    </div>
                </div>

                <!-- 教師使用說明 -->
                <div id="teacher" class="help-section">
                    <h2>👩‍🏫 教師使用指南</h2>

                    <h3>🔐 管理後台登入</h3>
                    <div class="code-block">
                        網址：admin_login.php<br>
                        預設帳號：admin<br>
                        預設密碼：admin123456
                    </div>
                    <a href="admin_login.php" class="btn-demo">進入管理後台</a>

                    <div class="alert-warning">
                        <strong>🔒 安全提醒：</strong>首次登入後請立即修改管理員密碼！
                    </div>

                    <h3>🏫 教室管理</h3>
                    <ol class="step-list">
                        <li>
                            <strong>創建教室</strong><br>
                            在管理面板點擊「教室管理」→「創建新教室」<br>
                            填寫教室名稱和分享代碼（建議使用英文+數字）
                        </li>
                        <li>
                            <strong>分享給學生</strong><br>
                            複製教室分享代碼，或提供完整連結：<br>
                            <code>upload.php?code=您的教室代碼</code>
                        </li>
                        <li>
                            <strong>管理教室</strong><br>
                            可以編輯教室資訊、啟用/停用教室、查看作業統計
                        </li>
                    </ol>

                    <h3>📊 作業評分與管理</h3>
                    <ol class="step-list">
                        <li>
                            <strong>查看作業列表</strong><br>
                            點擊「作業管理」查看所有學生作業<br>
                            可以按教室、評分狀態、觀摩狀態篩選
                        </li>
                        <li>
                            <strong>評分作業</strong><br>
                            點擊作業的「評分」按鈕<br>
                            輸入分數（0-100）和評語
                        </li>
                        <li>
                            <strong>設定觀摩狀態</strong><br>
                            點擊「觀摩」按鈕設定作業是否在首頁顯示<br>
                            可以設定為精選作業
                        </li>
                        <li>
                            <strong>編輯作業</strong><br>
                            可以修改學生的作業資訊、分配教室
                        </li>
                    </ol>

                    <h3>⚙️ 系統設定</h3>
                    <div class="feature-grid">
                        <div class="feature-card">
                            <div class="feature-icon">🔒</div>
                            <div class="feature-title">完全關閉</div>
                            <div class="feature-description">首頁不顯示任何作業，適合保護隱私</div>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">✋</div>
                            <div class="feature-title">手動審核（推薦）</div>
                            <div class="feature-description">需要老師審核通過才顯示，確保品質</div>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">🚀</div>
                            <div class="feature-title">自動公開</div>
                            <div class="feature-description">學生上傳後立即顯示，促進交流</div>
                        </div>
                    </div>

                    <div class="alert-info">
                        <strong>📋 教學建議：</strong><br>
                        1. 課程開始時創建教室並設定為「手動審核」模式<br>
                        2. 定期評分並給予回饋，篩選優秀作業設為精選<br>
                        3. 課程結束時可開放分數讓學生查看
                    </div>
                </div>

                <!-- 功能介紹 -->
                <div id="features" class="help-section">
                    <h2>✨ 平台功能介紹</h2>

                    <div class="feature-grid">
                        <div class="feature-card">
                            <div class="feature-icon">🏫</div>
                            <div class="feature-title">教室管理</div>
                            <div class="feature-description">
                                支援多班級管理，每個教室有獨立的分享代碼，方便學生加入和作業分類
                            </div>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">📝</div>
                            <div class="feature-title">作業上傳</div>
                            <div class="feature-description">
                                簡單快速的上傳流程，支援各種網站平台，自動生成作業展示卡片
                            </div>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">📊</div>
                            <div class="feature-title">評分系統</div>
                            <div class="feature-description">
                                完整的評分功能，支援分數和文字評語，可設定分數是否公開顯示
                            </div>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">👁️</div>
                            <div class="feature-title">觀摩控制</div>
                            <div class="feature-description">
                                三種觀摩模式，靈活控制作業展示，平衡隱私保護和學習交流
                            </div>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">⭐</div>
                            <div class="feature-title">精選展示</div>
                            <div class="feature-description">
                                優秀作業可設為精選，在首頁優先顯示，激勵學生創作更好的作品
                            </div>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">📱</div>
                            <div class="feature-title">響應式設計</div>
                            <div class="feature-description">
                                支援手機、平板、電腦等各種裝置，隨時隨地上傳和查看作業
                            </div>
                        </div>
                    </div>

                    <h3>🎯 使用場景</h3>
                    <ol class="step-list">
                        <li>
                            <strong>課堂作業展示</strong><br>
                            學生上傳網頁作業，全班可以互相觀摩學習，老師可以即時給予回饋
                        </li>
                        <li>
                            <strong>期末專題管理</strong><br>
                            管理學生的期末專題，進行評分和展示，建立作品集
                        </li>
                        <li>
                            <strong>程式設計競賽</strong><br>
                            收集參賽作品，進行評審和排名，展示優秀作品
                        </li>
                        <li>
                            <strong>教學資源庫</strong><br>
                            累積優秀學生作品，作為未來教學的範例和參考
                        </li>
                    </ol>

                    <div class="alert-info">
                        <strong>🔄 持續更新：</strong>平台會根據使用回饋持續改進功能，如有建議歡迎反饋！
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showSection(sectionId) {
            // 隱藏所有區塊
            document.querySelectorAll('.help-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // 移除所有標籤的 active 狀態
            document.querySelectorAll('.help-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // 顯示選中的區塊
            document.getElementById(sectionId).classList.add('active');
            
            // 設定對應標籤為 active
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
