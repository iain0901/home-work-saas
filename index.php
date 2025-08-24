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

// 處理教室密碼驗證
$password_error = '';
if (isset($_POST['action']) && $_POST['action'] === 'join_classroom') {
    $classroom_id = $_POST['classroom_id'];
    $password = $_POST['password'] ?? '';
    
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM classrooms WHERE id = ? AND is_active = 1");
        $stmt->execute([$classroom_id]);
        $classroom = $stmt->fetch();
        
        if ($classroom) {
            $can_join = false;
            
            if ($classroom['require_password']) {
                if ($password === $classroom['password']) {
                    $can_join = true;
                } else {
                    $password_error = '密碼錯誤，請重新輸入';
                }
            } else {
                $can_join = true;
            }
            
            if ($can_join) {
                // 記錄學生已加入該教室
                $stmt = $db->prepare("INSERT IGNORE INTO student_classroom_access (student_cookie, classroom_id) VALUES (?, ?)");
                $stmt->execute([$student_cookie, $classroom_id]);
                
                // 重定向到該教室的作業展示頁面
                header('Location: classroom.php?id=' . $classroom_id);
                exit;
            }
        } else {
            $password_error = '教室不存在或已停用';
        }
    } catch (Exception $e) {
        $password_error = '系統錯誤：' . $e->getMessage();
    }
}

// 獲取所有啟用的教室
$classrooms = [];
$student_joined_classrooms = [];
try {
    $db = getDB();
    
    // 獲取所有啟用的教室
    $stmt = $db->query("SELECT * FROM classrooms WHERE is_active = 1 ORDER BY name");
    $classrooms = $stmt->fetchAll();
    
    // 獲取學生已加入的教室ID列表
    if ($student_cookie) {
        $stmt = $db->prepare("SELECT classroom_id FROM student_classroom_access WHERE student_cookie = ?");
        $stmt->execute([$student_cookie]);
        $student_joined_classrooms = array_column($stmt->fetchAll(), 'classroom_id');
    }
} catch (Exception $e) {
    $db_error = "數據庫連接失敗，請聯繫管理員";
}

// 獲取統計資訊
$total_classrooms = count($classrooms);
$total_assignments = 0;
try {
    if ($db) {
        $stmt = $db->query("SELECT COUNT(*) as count FROM assignments WHERE is_public = 1");
        $result = $stmt->fetch();
        $total_assignments = $result['count'];
    }
} catch (Exception $e) {
    // 忽略錯誤
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($config['school_name']); ?> - 教室選擇</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="checkbox-styles.css?v=<?php echo time(); ?>">
    <style>
        .classroom-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .classroom-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
        }
        
        .classroom-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .classroom-card.joined {
            border-color: #28a745;
            background: linear-gradient(135deg, #f8fff9 0%, #ffffff 100%);
        }
        
        .classroom-card.joined::before {
            content: "已加入";
            position: absolute;
            top: 15px;
            right: 15px;
            background: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7em;
            font-weight: bold;
        }
        
        .classroom-card.password-required {
            border-color: #ffc107;
            background: linear-gradient(135deg, #fffbf0 0%, #ffffff 100%);
        }
        
        .classroom-card.password-required::after {
            content: "🔒";
            position: absolute;
            top: 15px;
            left: 15px;
            font-size: 1.2em;
        }
        
        .classroom-header {
            margin-bottom: 15px;
        }
        
        .classroom-name {
            font-size: 1.3em;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .classroom-description {
            color: #6c757d;
            font-size: 0.9em;
            line-height: 1.4;
            margin-bottom: 15px;
            min-height: 40px;
        }
        
        .classroom-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 0.85em;
            color: #6c757d;
        }
        
        .classroom-actions {
            text-align: center;
        }
        
        .btn-join {
            width: 100%;
            padding: 12px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn-join:hover {
            background: #0056b3;
        }
        
        .btn-join.joined {
            background: #28a745;
        }
        
        .btn-join.joined:hover {
            background: #218838;
        }
        
        .password-form {
            margin-top: 15px;
            display: none;
        }
        
        .password-form.show {
            display: block;
        }
        
        .password-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-bottom: 10px;
            font-size: 0.9em;
        }
        
        .password-error {
            color: #dc3545;
            font-size: 0.8em;
            margin-bottom: 10px;
        }
        
        .no-classrooms {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .no-classrooms-icon {
            font-size: 4em;
            margin-bottom: 20px;
        }
        
        .intro-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .intro-section h2 {
            margin-bottom: 15px;
            font-size: 1.8em;
        }
        
        .intro-section p {
            font-size: 1.1em;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .stats-quick {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 20px;
        }
        
        .stat-quick {
            text-align: center;
        }
        
        .stat-quick-number {
            font-size: 2em;
            font-weight: bold;
            display: block;
        }
        
        .stat-quick-label {
            font-size: 0.9em;
            opacity: 0.8;
        }
        
        .btn-lottery {
            background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3);
        }
        
        .btn-lottery:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
            color: white;
        }
        
        @media (max-width: 768px) {
            .classroom-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .stats-quick {
                gap: 20px;
            }
            
            .stat-quick-number {
                font-size: 1.5em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><?php echo htmlspecialchars($config['school_name']); ?></h1>
            <div class="header-actions">
                <a href="lottery.php" class="btn-lottery">🎲 抽獎</a>
                <a href="help.php" class="btn-view">使用說明</a>
                <a href="admin_login.php" class="btn-admin">管理</a>
            </div>
        </header>

        <div class="intro-section">
            <h2>🏫 歡迎來到作業展示平台</h2>
            <p>請選擇您要進入的教室，查看和上傳作業</p>
            <div class="stats-quick">
                <div class="stat-quick">
                    <span class="stat-quick-number"><?php echo $total_classrooms; ?></span>
                    <span class="stat-quick-label">個教室</span>
                </div>
                <div class="stat-quick">
                    <span class="stat-quick-number"><?php echo $total_assignments; ?></span>
                    <span class="stat-quick-label">個作業</span>
                </div>
            </div>
        </div>

        <?php if (isset($db_error)): ?>
            <div class="alert error"><?php echo $db_error; ?></div>
        <?php endif; ?>

        <section class="classrooms-section">
            <h3>📚 選擇教室</h3>
            
            <?php if (empty($classrooms)): ?>
                <div class="no-classrooms">
                    <div class="no-classrooms-icon">🏫</div>
                    <h4>目前還沒有可用的教室</h4>
                    <p>請聯繫老師創建教室，或等待教室開放。</p>
                </div>
            <?php else: ?>
                <div class="classroom-grid">
                    <?php foreach ($classrooms as $classroom): ?>
                        <?php 
                        $is_joined = in_array($classroom['id'], $student_joined_classrooms);
                        $needs_password = $classroom['require_password'] && !$is_joined;
                        ?>
                        <div class="classroom-card <?php echo $is_joined ? 'joined' : ''; ?> <?php echo $needs_password ? 'password-required' : ''; ?>">
                            <div class="classroom-header">
                                <div class="classroom-name"><?php echo htmlspecialchars($classroom['name']); ?></div>
                                <?php if ($classroom['description']): ?>
                                    <div class="classroom-description"><?php echo htmlspecialchars($classroom['description']); ?></div>
                                <?php else: ?>
                                    <div class="classroom-description">歡迎加入本教室！</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="classroom-info">
                                <span>代碼: <?php echo htmlspecialchars($classroom['share_code']); ?></span>
                                <span><?php echo date('m/d', strtotime($classroom['created_at'])); ?> 創建</span>
                            </div>
                            
                            <div class="classroom-actions">
                                <?php if ($is_joined): ?>
                                    <a href="classroom.php?id=<?php echo $classroom['id']; ?>" class="btn-join joined">
                                        📚 進入教室
                                    </a>
                                <?php else: ?>
                                    <?php if ($needs_password): ?>
                                        <button class="btn-join" onclick="showPasswordForm(<?php echo $classroom['id']; ?>)">
                                            🔒 需要密碼
                                        </button>
                                        <form method="POST" class="password-form" id="password-form-<?php echo $classroom['id']; ?>">
                                            <input type="hidden" name="action" value="join_classroom">
                                            <input type="hidden" name="classroom_id" value="<?php echo $classroom['id']; ?>">
                                            <?php if ($password_error && $_POST['classroom_id'] == $classroom['id']): ?>
                                                <div class="password-error"><?php echo htmlspecialchars($password_error); ?></div>
                                            <?php endif; ?>
                                            <input type="password" name="password" class="password-input" 
                                                   placeholder="請輸入教室密碼" required>
                                            <button type="submit" class="btn-join">🔓 加入教室</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="width: 100%;">
                                            <input type="hidden" name="action" value="join_classroom">
                                            <input type="hidden" name="classroom_id" value="<?php echo $classroom['id']; ?>">
                                            <button type="submit" class="btn-join">
                                                🚀 立即加入
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- 使用提示 -->
        <section style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #17a2b8;">
            <h4 style="margin-top: 0; color: #0c5460;">💡 使用提示</h4>
            <ul style="color: #0c5460; margin-bottom: 0;">
                <li><strong>🔓 無密碼教室：</strong>點擊「立即加入」即可進入</li>
                <li><strong>🔒 有密碼教室：</strong>需要輸入老師提供的密碼</li>
                <li><strong>✅ 已加入教室：</strong>下次可以直接進入，無需重新輸入密碼</li>
                <li><strong>🔗 分享連結：</strong>使用老師提供的分享連結可以直接加入教室</li>
            </ul>
        </section>
    </div>

    <script>
        // 顯示密碼輸入表單
        function showPasswordForm(classroomId) {
            const form = document.getElementById('password-form-' + classroomId);
            if (form) {
                form.classList.add('show');
                const passwordInput = form.querySelector('input[type="password"]');
                if (passwordInput) {
                    passwordInput.focus();
                }
            }
        }

        // 如果有密碼錯誤，自動顯示對應的密碼表單
        <?php if ($password_error && isset($_POST['classroom_id'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                showPasswordForm(<?php echo $_POST['classroom_id']; ?>);
            });
        <?php endif; ?>
    </script>
</body>
</html>