<?php
session_start();

// 檢查登入狀態
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: admin_login.php');
    exit;
}

// 載入配置助手
require_once 'config_helper.php';
require_once 'db_config.php';
$config = get_config();

// 從MySQL讀取作業資料
$assignments = [];
try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM assignments ORDER BY submit_time DESC");
    $assignments = $stmt->fetchAll();
} catch (Exception $e) {
    $db_error = "數據庫連接失敗：" . $e->getMessage();
}

$success_message = '';
$error_message = '';

// 處理登出
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: admin_login.php');
    exit;
}

// 處理學校名稱更新
if (isset($_POST['action']) && $_POST['action'] === 'update_school') {
    $new_school_name = trim($_POST['school_name']);
    $new_platform_title = trim($_POST['platform_title']);
    
    if (!empty($new_school_name) && !empty($new_platform_title)) {
        try {
            if (update_config('school_name', $new_school_name) && update_config('platform_title', $new_platform_title)) {
                $success_message = '學校資訊更新成功！';
                // 重新載入配置
                $config = get_config();
            } else {
                $error_message = '更新失敗，請檢查數據庫連接';
            }
        } catch (Exception $e) {
            $error_message = '更新失敗：' . $e->getMessage();
        }
    } else {
        $error_message = '請填寫完整的學校資訊';
    }
}

// 處理作業刪除
if (isset($_POST['action']) && $_POST['action'] === 'delete_assignment') {
    $delete_id = $_POST['assignment_id'];
    
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM assignments WHERE id = ?");
        $stmt->execute([$delete_id]);
        
        if ($stmt->rowCount() > 0) {
            $success_message = '作業刪除成功！';
            // 重新載入作業列表
            $stmt = $db->query("SELECT * FROM assignments ORDER BY submit_time DESC");
            $assignments = $stmt->fetchAll();
        } else {
            $error_message = '刪除失敗，作業不存在';
        }
    } catch (Exception $e) {
        $error_message = '刪除失敗：' . $e->getMessage();
    }
}

// 統計資訊
$total_assignments = count($assignments);
$total_groups = count(array_unique(array_column($assignments, 'group_name')));
$recent_assignments = array_slice($assignments, 0, 5);
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理面板 - <?php echo htmlspecialchars($config['school_name'] ?? '學校'); ?></title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .admin-navigation {
            margin-bottom: 30px;
        }
        
        .nav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .nav-item {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .nav-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-color: #007bff;
        }
        
        .nav-icon {
            font-size: 3em;
            text-align: center;
            margin-bottom: 15px;
        }
        
        .nav-title {
            font-size: 1.3em;
            font-weight: bold;
            color: #2c3e50;
            text-align: center;
            margin-bottom: 10px;
        }
        
        .nav-description {
            font-size: 0.9em;
            color: #6c757d;
            text-align: center;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><?php echo htmlspecialchars($config['school_name'] ?? '學校'); ?></h1>
            <h2>管理面板</h2>
            <div class="header-actions">
                <a href="index.php" class="btn-view">查看首頁</a>
                <a href="help.php" class="btn-view">使用說明</a>
                <a href="?action=logout" class="btn-logout">登出</a>
            </div>
        </header>

        <!-- 管理功能導航 -->
        <section class="admin-navigation">
            <h3>🎛️ 管理功能</h3>
            <div class="nav-grid">
                <a href="admin_assignments.php" class="nav-item">
                    <div class="nav-icon">📝</div>
                    <div class="nav-title">作業管理</div>
                    <div class="nav-description">評分、編輯、觀摩設定</div>
                </a>
                <a href="admin_classrooms.php" class="nav-item">
                    <div class="nav-icon">🏫</div>
                    <div class="nav-title">教室管理</div>
                    <div class="nav-description">創建、分享教室代碼</div>
                </a>
                <a href="admin_settings.php" class="nav-item">
                    <div class="nav-icon">⚙️</div>
                    <div class="nav-title">系統設定</div>
                    <div class="nav-description">觀摩模式、評分設定</div>
                </a>
            </div>
        </section>

        <?php if ($success_message): ?>
            <div class="alert success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <!-- 快速統計 -->
        <section class="admin-stats">
            <h3>📊 平台概況</h3>
            <div class="stats-grid">
                <div class="stat-item" data-icon="📚">
                    <div class="stat-number"><?php echo $total_assignments; ?></div>
                    <div class="stat-label">總作業數</div>
                </div>
                <div class="stat-item" data-icon="👥">
                    <div class="stat-number"><?php echo $total_groups; ?></div>
                    <div class="stat-label">參與組別</div>
                </div>
                <div class="stat-item" data-icon="⏰">
                    <div class="stat-number"><?php echo date('H:i'); ?></div>
                    <div class="stat-label">當前時間</div>
                </div>
            </div>
        </section>

        <!-- 學校資訊設定 -->
        <section class="admin-settings">
            <h3>⚙️ 學校資訊設定</h3>
            <form method="POST" class="settings-form">
                <input type="hidden" name="action" value="update_school">
                
                <div class="form-group">
                    <label for="school_name">學校名稱：</label>
                    <input type="text" id="school_name" name="school_name" required 
                           value="<?php echo htmlspecialchars($config['school_name'] ?? ''); ?>" 
                           placeholder="請輸入學校名稱">
                </div>

                <div class="form-group">
                    <label for="platform_title">平台標題：</label>
                    <input type="text" id="platform_title" name="platform_title" required 
                           value="<?php echo htmlspecialchars($config['platform_title'] ?? ''); ?>" 
                           placeholder="請輸入平台標題">
                </div>

                <button type="submit" class="btn-submit">更新設定</button>
            </form>
        </section>

        <!-- 最近作業 -->
        <section class="admin-recent">
            <h3>📝 最近上傳的作業</h3>
            <?php if (empty($recent_assignments)): ?>
                <div class="no-data">
                    <div class="no-data-icon">📝</div>
                    <h4>目前還沒有作業</h4>
                    <p>等待學生上傳作業</p>
                </div>
            <?php else: ?>
                <div class="recent-assignments">
                    <?php foreach ($recent_assignments as $assignment): ?>
                        <div class="recent-item">
                            <div class="recent-header">
                                <h4><?php echo htmlspecialchars($assignment['title']); ?></h4>
                                <span class="group-badge"><?php echo htmlspecialchars($assignment['group_name']); ?></span>
                            </div>
                            <div class="recent-info">
                                <p><strong>姓名：</strong><?php echo htmlspecialchars($assignment['student_name']); ?></p>
                                <p><strong>網址：</strong><a href="<?php echo htmlspecialchars($assignment['url']); ?>" target="_blank"><?php echo htmlspecialchars($assignment['url']); ?></a></p>
                                <p><strong>上傳時間：</strong><?php echo htmlspecialchars($assignment['submit_time']); ?></p>
                            </div>
                            <div class="recent-actions">
                                <a href="<?php echo htmlspecialchars($assignment['url']); ?>" target="_blank" class="btn-visit-small">查看</a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('確定要刪除這個作業嗎？')">
                                    <input type="hidden" name="action" value="delete_assignment">
                                    <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                    <button type="submit" class="btn-delete-small">刪除</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($total_assignments > 5): ?>
                    <div class="view-all-link">
                        <a href="admin_assignments.php" class="btn-view-all-assignments">查看所有作業 (<?php echo $total_assignments; ?>)</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>

    <script src="script.js"></script>
</body>
</html>
