<?php
session_start();

// 載入配置助手
require_once 'config_helper.php';
require_once 'db_config.php';
$config = get_config();

// 獲取學生識別cookie
$student_cookie = $_COOKIE['student_id'] ?? null;
if (!$student_cookie) {
    $student_cookie = 'student_' . uniqid() . '_' . time();
    setcookie('student_id', $student_cookie, time() + (365 * 24 * 60 * 60), '/'); // 1年有效期
}

// 檢查是否通過URL參數指定教室
$target_classroom_id = null;
$target_classroom = null;
$auto_join_success = false;

// 處理分享連結加入（通過code參數）
if (isset($_GET['code'])) {
    $share_code = strtoupper(trim($_GET['code']));
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM classrooms WHERE share_code = ? AND is_active = 1");
        $stmt->execute([$share_code]);
        $target_classroom = $stmt->fetch();
        
        if ($target_classroom) {
            $target_classroom_id = $target_classroom['id'];
            
            // 自動加入教室（分享連結不需要密碼）
            $stmt = $db->prepare("INSERT IGNORE INTO student_classroom_access (student_cookie, classroom_id) VALUES (?, ?)");
            $stmt->execute([$student_cookie, $target_classroom_id]);
            
            $auto_join_success = true;
        }
    } catch (Exception $e) {
        // 忽略錯誤
    }
}

// 處理直接指定教室ID（從classroom.php跳轉）
if (!$target_classroom_id && isset($_GET['classroom_id'])) {
    $classroom_id = $_GET['classroom_id'];
    try {
        $db = getDB();
        
        // 檢查學生是否有權限訪問該教室
        $stmt = $db->prepare("
            SELECT c.* FROM classrooms c 
            JOIN student_classroom_access sca ON c.id = sca.classroom_id 
            WHERE c.id = ? AND c.is_active = 1 AND sca.student_cookie = ?
        ");
        $stmt->execute([$classroom_id, $student_cookie]);
        $target_classroom = $stmt->fetch();
        
        if ($target_classroom) {
            $target_classroom_id = $target_classroom['id'];
        }
    } catch (Exception $e) {
        // 忽略錯誤
    }
}

// 如果沒有指定教室，重定向到首頁選擇教室
if (!$target_classroom_id) {
    header('Location: index.php');
    exit;
}

// 處理上傳表單
if (isset($_POST['action']) && $_POST['action'] === 'submit') {
    try {
        $assignment_id = uniqid();
        $submitter_cookie = uniqid();
        
        // 新作業預設為不公開，需要管理員手動設定為公開
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO assignments (id, group_name, student_name, title, url, submitter_cookie, classroom_id, is_public, submit_time) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())");
        $stmt->execute([
            $assignment_id,
            $_POST['group'],
            $_POST['name'],
            $_POST['title'],
            $_POST['url'],
            $submitter_cookie,
            $target_classroom_id
        ]);
        
        // 設定Cookie
        setcookie('submitter_cookie', $submitter_cookie, time() + (86400 * 30), '/');
        
        $success_message = "作業上傳成功！您的作業已成功提交到「" . htmlspecialchars($target_classroom['name']) . "」教室。作業將在管理員設定為公開後顯示。";
        $show_success = true;
        
    } catch (Exception $e) {
        $error_message = "上傳失敗：" . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($target_classroom['name']); ?> - 作業上傳</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .classroom-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .classroom-banner h2 {
            margin: 0 0 10px 0;
            font-size: 1.5em;
        }
        
        .classroom-banner p {
            margin: 0;
            opacity: 0.9;
        }
        
        .breadcrumb {
            margin-bottom: 20px;
            font-size: 0.9em;
            color: #6c757d;
        }
        
        .breadcrumb a {
            color: #007bff;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .auto-join-notice {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #17a2b8;
            margin-bottom: 20px;
        }
        
        .auto-join-notice h4 {
            margin-top: 0;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <nav class="breadcrumb">
            <a href="index.php">🏠 教室選擇</a> > 
            <a href="classroom.php?id=<?php echo $target_classroom_id; ?>"><?php echo htmlspecialchars($target_classroom['name']); ?></a> > 
            <span>上傳作業</span>
        </nav>

        <div class="classroom-banner">
            <h2>🏫 <?php echo htmlspecialchars($target_classroom['name']); ?></h2>
            <p>📝 作業上傳平台</p>
            <?php if ($target_classroom['description']): ?>
                <p style="margin-top: 10px; font-size: 0.9em;"><?php echo htmlspecialchars($target_classroom['description']); ?></p>
            <?php endif; ?>
        </div>

        <?php if ($auto_join_success): ?>
            <div class="auto-join-notice">
                <h4>🎉 歡迎加入教室！</h4>
                <p>您已成功加入「<?php echo htmlspecialchars($target_classroom['name']); ?>」教室，下次可以直接進入此教室。</p>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (isset($show_success) && $show_success): ?>
            <div class="alert success">
                <?php echo $success_message; ?>
                <div style="margin-top: 15px;">
                    <a href="classroom.php?id=<?php echo $target_classroom_id; ?>" class="btn-view">查看教室作業</a>
                    <a href="upload.php?classroom_id=<?php echo $target_classroom_id; ?>" class="btn-upload" style="margin-left: 10px;">繼續上傳</a>
                </div>
            </div>
        <?php else: ?>
            <section class="upload-form">
                <h3>📝 上傳作業</h3>
                <form method="POST" class="form-container">
                    <input type="hidden" name="action" value="submit">
                    
                    <div class="form-group">
                        <label for="group">組別：</label>
                        <input type="text" id="group" name="group" required 
                               placeholder="請輸入您的組別（例如：第一組）">
                        <div class="help-text">請輸入您所屬的組別名稱</div>
                    </div>

                    <div class="form-group">
                        <label for="name">姓名：</label>
                        <input type="text" id="name" name="name" required 
                               placeholder="請輸入您的姓名">
                        <div class="help-text">請輸入您的真實姓名</div>
                    </div>

                    <div class="form-group">
                        <label for="title">網站標題：</label>
                        <input type="text" id="title" name="title" required 
                               placeholder="請輸入您的網站標題">
                        <div class="help-text">簡潔有力的標題能讓人一眼就明白您的作品主題</div>
                    </div>

                    <div class="form-group">
                        <label for="url">網站網址：</label>
                        <input type="url" id="url" name="url" required 
                               placeholder="https://example.com">
                        <div class="help-text">請確保網址正確且可以正常訪問</div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">🚀 上傳作業</button>
                        <a href="classroom.php?id=<?php echo $target_classroom_id; ?>" class="btn-cancel">🔙 返回教室</a>
                    </div>
                </form>
            </section>

            <!-- 使用提示 -->
            <section style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #17a2b8;">
                <h4 style="margin-top: 0; color: #0c5460;">💡 上傳提示</h4>
                <ul style="color: #0c5460; margin-bottom: 0;">
                    <li><strong>📝 填寫完整：</strong>請確保所有欄位都填寫完整且正確</li>
                    <li><strong>🔗 網址檢查：</strong>上傳前請先測試網址是否可以正常開啟</li>
                    <li><strong>👀 作業審核：</strong>上傳的作業需要老師審核後才會公開顯示</li>
                    <li><strong>✏️ 後續編輯：</strong>上傳後您仍可以編輯或刪除自己的作業</li>
                </ul>
            </section>
        <?php endif; ?>
    </div>

    <script>
        // 表單驗證
        document.querySelector('form').addEventListener('submit', function(e) {
            const url = document.getElementById('url').value;
            
            // 檢查網址格式
            try {
                new URL(url);
            } catch (error) {
                e.preventDefault();
                alert('請輸入有效的網址格式（例如：https://example.com）');
                return false;
            }
            
            // 確認提交
            if (!confirm('確定要上傳這個作業嗎？\n\n請確保所有資訊都正確填寫。')) {
                e.preventDefault();
                return false;
            }
        });

        // 自動聚焦到第一個輸入欄位
        document.addEventListener('DOMContentLoaded', function() {
            const firstInput = document.getElementById('group');
            if (firstInput) {
                firstInput.focus();
            }
        });
    </script>
</body>
</html>