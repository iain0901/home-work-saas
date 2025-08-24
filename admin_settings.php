<?php
session_start();

// 檢查登入狀態
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: admin_login.php');
    exit;
}

require_once 'config_helper.php';
require_once 'db_config.php';
$config = get_config();

$success_message = '';
$error_message = '';

// 處理設定更新
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'update_basic_settings') {
            // 基本設定
            $school_name = trim($_POST['school_name']);
            $platform_title = trim($_POST['platform_title']);
            
            if (empty($school_name) || empty($platform_title)) {
                throw new Exception('學校名稱和平台標題不能為空');
            }
            
            update_config('school_name', $school_name);
            update_config('platform_title', $platform_title);
            
            $success_message = '基本設定更新成功！';
            
        } elseif ($action === 'update_scoring_settings') {
            // 評分設定
            $score_visibility = $_POST['score_visibility'];
            $allow_score_comments = isset($_POST['allow_score_comments']) ? '1' : '0';
            $enable_featured = isset($_POST['enable_featured']) ? '1' : '0';
            $max_score = floatval($_POST['max_score']);
            
            if ($max_score <= 0 || $max_score > 1000) {
                throw new Exception('最高分數必須在 1 到 1000 之間');
            }
            
            update_config('score_visibility', $score_visibility);
            update_config('allow_score_comments', $allow_score_comments);
            update_config('enable_featured', $enable_featured);
            update_config('max_score', $max_score);
            
            $success_message = '評分設定更新成功！';
            
        } elseif ($action === 'update_admin_password') {
            // 管理員密碼
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            if ($current_password !== $config['admin_password']) {
                throw new Exception('當前密碼不正確');
            }
            
            if (strlen($new_password) < 6) {
                throw new Exception('新密碼長度至少需要 6 個字符');
            }
            
            if ($new_password !== $confirm_password) {
                throw new Exception('新密碼確認不一致');
            }
            
            update_config('admin_password', $new_password);
            
            $success_message = '管理員密碼更新成功！';
        }
        
        // 重新載入配置
        $config = get_config();
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// 獲取統計資訊
try {
    $db = getDB();
    
    // 總作業數
    $stmt = $db->query("SELECT COUNT(*) FROM assignments");
    $total_assignments = $stmt->fetchColumn();
    
    // 各觀摩狀態統計
    $stmt = $db->query("SELECT showcase_status, COUNT(*) as count FROM assignments GROUP BY showcase_status");
    $showcase_stats = [];
    while ($row = $stmt->fetch()) {
        $showcase_stats[$row['showcase_status']] = $row['count'];
    }
    
    // 評分統計
    $stmt = $db->query("SELECT COUNT(*) FROM assignments WHERE score IS NOT NULL");
    $scored_assignments = $stmt->fetchColumn();
    
    // 精選作業數
    $stmt = $db->query("SELECT COUNT(*) FROM assignments WHERE is_featured = 1");
    $featured_assignments = $stmt->fetchColumn();
    
    // 教室數量
    $stmt = $db->query("SELECT COUNT(*) FROM classrooms WHERE is_active = 1");
    $active_classrooms = $stmt->fetchColumn();
    
} catch (Exception $e) {
    $error_message = "無法載入統計資訊：" . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系統設定 - <?php echo htmlspecialchars($config['school_name'] ?? '學校'); ?></title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .settings-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 768px) {
            .settings-container {
                grid-template-columns: 1fr;
            }
        }
        
        .settings-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .settings-section h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #495057;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin: 0;
        }
        
        .help-text {
            font-size: 0.85em;
            color: #6c757d;
            margin-top: 5px;
            line-height: 1.4;
        }
        
        .showcase-mode-options {
            display: grid;
            gap: 15px;
            margin-top: 10px;
        }
        
        .mode-option {
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .mode-option:hover {
            border-color: #007bff;
            background: #f8f9ff;
        }
        
        .mode-option.selected {
            border-color: #007bff;
            background: #e7f3ff;
        }
        
        .mode-option input[type="radio"] {
            margin-right: 10px;
        }
        
        .mode-title {
            font-weight: bold;
            color: #495057;
            margin-bottom: 5px;
        }
        
        .mode-description {
            font-size: 0.9em;
            color: #6c757d;
            line-height: 1.4;
        }
        
        .stats-overview {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        .btn-submit {
            background: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background 0.2s ease;
            width: 100%;
        }
        
        .btn-submit:hover {
            background: #0056b3;
        }
        
        .btn-danger {
            background: #dc3545;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 0.8em;
        }
        
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #17a2b8;
        }
        
        .alert-info h4 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #0c5460;
        }
        
        .alert-info ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        
        .alert-info li {
            margin: 8px 0;
            line-height: 1.5;
        }
        
        .btn-demo {
            display: inline-block;
            padding: 10px 20px;
            background: #17a2b8;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.3s ease;
        }
        
        .btn-demo:hover {
            background: #138496;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><?php echo htmlspecialchars($config['school_name'] ?? '學校'); ?></h1>
            <h2>⚙️ 系統設定</h2>
            <div class="header-actions">
                <a href="admin.php" class="btn-view">返回管理面板</a>
                <a href="admin_assignments.php" class="btn-view">作業管理</a>
                <a href="?action=logout" class="btn-logout">登出</a>
            </div>
        </header>

        <?php if ($success_message): ?>
            <div class="alert success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <!-- 統計概覽 -->
        <div class="stats-overview">
            <h3>📊 系統概覽</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $total_assignments ?? 0; ?></div>
                    <div class="stat-label">總作業數</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $scored_assignments ?? 0; ?></div>
                    <div class="stat-label">已評分作業</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $featured_assignments ?? 0; ?></div>
                    <div class="stat-label">精選作業</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $active_classrooms ?? 0; ?></div>
                    <div class="stat-label">活躍教室</div>
                </div>
            </div>
        </div>

        <div class="settings-container">
            <!-- 基本設定 -->
            <div class="settings-section">
                <h3>🏫 基本設定</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update_basic_settings">
                    
                    <div class="form-group">
                        <label for="school_name">學校名稱</label>
                        <input type="text" id="school_name" name="school_name" required 
                               value="<?php echo htmlspecialchars($config['school_name'] ?? ''); ?>">
                        <div class="help-text">顯示在網站標題和頁面頂部的學校名稱</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="platform_title">平台標題</label>
                        <input type="text" id="platform_title" name="platform_title" required 
                               value="<?php echo htmlspecialchars($config['platform_title'] ?? ''); ?>">
                        <div class="help-text">平台的副標題，描述平台用途</div>
                    </div>
                    
                    <button type="submit" class="btn-submit">更新基本設定</button>
                </form>
            </div>

            <!-- 評分設定 -->
            <div class="settings-section">
                <h3>📊 評分設定</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update_scoring_settings">
                    
                    <div class="form-group">
                        <label for="max_score">最高分數</label>
                        <input type="number" id="max_score" name="max_score" min="1" max="1000" step="0.5" required 
                               value="<?php echo $config['max_score'] ?? 100; ?>">
                        <div class="help-text">設定作業評分的最高分數</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="score_visibility">分數可見性</label>
                        <select id="score_visibility" name="score_visibility" required>
                            <option value="private" <?php echo ($config['score_visibility'] ?? 'private') === 'private' ? 'selected' : ''; ?>>
                                私人 - 只有管理員可見
                            </option>
                            <option value="public" <?php echo ($config['score_visibility'] ?? 'private') === 'public' ? 'selected' : ''; ?>>
                                公開 - 所有人可見
                            </option>
                        </select>
                        <div class="help-text">控制作業分數是否對學生和訪客公開顯示</div>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="allow_score_comments" name="allow_score_comments" value="1" 
                               <?php echo ($config['allow_score_comments'] ?? '1') === '1' ? 'checked' : ''; ?>>
                        <label for="allow_score_comments">允許評分備註</label>
                    </div>
                    <div class="help-text">管理員評分時可以添加文字備註</div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="enable_featured" name="enable_featured" value="1" 
                               <?php echo ($config['enable_featured'] ?? '1') === '1' ? 'checked' : ''; ?>>
                        <label for="enable_featured">啟用精選功能</label>
                    </div>
                    <div class="help-text">允許管理員將優秀作業設為精選，在首頁優先顯示</div>
                    
                    <button type="submit" class="btn-submit">更新評分設定</button>
                </form>
            </div>
        </div>

        <!-- 作業管理說明 -->
        <div class="settings-section">
            <h3>👁️ 作業觀摩說明</h3>
            <div class="alert-info">
                <h4>📋 作業公開機制</h4>
                <p>系統採用簡化的公開控制機制：</p>
                <ul>
                    <li><strong>🔒 預設不公開：</strong>學生上傳的作業預設為不公開狀態</li>
                    <li><strong>👁️ 管理員控制：</strong>只有管理員可以將作業設定為公開或不公開</li>
                    <li><strong>🏠 首頁顯示：</strong>只有設定為公開的作業會在首頁顯示</li>
                    <li><strong>⭐ 精選功能：</strong>公開的作業可以進一步設為精選，優先顯示</li>
                </ul>
                
                <h4>🎯 操作方式</h4>
                <p>前往「作業管理」頁面，點擊各作業的「公開設定」按鈕即可控制：</p>
                <ul>
                    <li>✅ 勾選「設為公開作業」→ 作業會在首頁顯示</li>
                    <li>❌ 取消勾選 → 作業不會在首頁顯示</li>
                    <li>⭐ 勾選「設為精選作業」→ 在首頁優先顯示（需先設為公開）</li>
                </ul>
                
                <div style="margin-top: 20px;">
                    <a href="admin_assignments.php" class="btn-demo">前往作業管理</a>
                </div>
            </div>
        </div>

        <!-- 安全設定 -->
        <div class="settings-section">
            <h3>🔐 安全設定</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_admin_password">
                
                <div class="form-group">
                    <label for="current_password">當前密碼</label>
                    <input type="password" id="current_password" name="current_password" required>
                    <div class="help-text">請輸入當前的管理員密碼</div>
                </div>
                
                <div class="form-group">
                    <label for="new_password">新密碼</label>
                    <input type="password" id="new_password" name="new_password" required minlength="6" 
                           onkeyup="checkPasswordStrength(this.value)">
                    <div id="password-strength" class="password-strength"></div>
                    <div class="help-text">密碼長度至少 6 個字符，建議包含大小寫字母、數字和特殊符號</div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">確認新密碼</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    <div class="help-text">請再次輸入新密碼進行確認</div>
                </div>
                
                <button type="submit" class="btn-submit btn-danger">更新管理員密碼</button>
            </form>
        </div>
    </div>

    <script>
        // 觀摩模式選擇
        document.querySelectorAll('input[name="showcase_mode"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.mode-option').forEach(function(option) {
                    option.classList.remove('selected');
                });
                this.closest('.mode-option').classList.add('selected');
            });
        });

        // 密碼強度檢查
        function checkPasswordStrength(password) {
            const strengthElement = document.getElementById('password-strength');
            let strength = 0;
            let feedback = '';

            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;

            if (strength <= 2) {
                feedback = '密碼強度：弱';
                strengthElement.className = 'password-strength strength-weak';
            } else if (strength <= 4) {
                feedback = '密碼強度：中等';
                strengthElement.className = 'password-strength strength-medium';
            } else {
                feedback = '密碼強度：強';
                strengthElement.className = 'password-strength strength-strong';
            }

            strengthElement.textContent = feedback;
        }

        // 密碼確認檢查
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && newPassword !== confirmPassword) {
                this.setCustomValidity('密碼確認不一致');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
