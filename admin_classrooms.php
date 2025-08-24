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

// 處理教室操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $db = getDB();
        
        if ($action === 'create_classroom') {
            // 創建新教室
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $share_code = strtoupper(trim($_POST['share_code']));
            $require_password = isset($_POST['require_password']) ? 1 : 0;
            $password = $require_password ? trim($_POST['password']) : null;
            
            if (empty($name) || empty($share_code)) {
                throw new Exception('教室名稱和分享代碼不能為空');
            }
            
            if ($require_password && empty($password)) {
                throw new Exception('啟用密碼保護時，密碼不能為空');
            }
            
            // 檢查分享代碼是否已存在
            $stmt = $db->prepare("SELECT id FROM classrooms WHERE share_code = ?");
            $stmt->execute([$share_code]);
            if ($stmt->fetch()) {
                throw new Exception('分享代碼已存在，請使用其他代碼');
            }
            
            $stmt = $db->prepare("INSERT INTO classrooms (name, description, share_code, require_password, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $share_code, $require_password, $password]);
            
            $success_message = '教室創建成功！分享代碼：' . $share_code . ($require_password ? '（已啟用密碼保護）' : '');
            
        } elseif ($action === 'update_classroom') {
            // 更新教室
            $id = $_POST['classroom_id'];
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $require_password = isset($_POST['require_password']) ? 1 : 0;
            $password = $require_password ? trim($_POST['password']) : null;
            
            if ($require_password && empty($password)) {
                throw new Exception('啟用密碼保護時，密碼不能為空');
            }
            
            $stmt = $db->prepare("UPDATE classrooms SET name = ?, description = ?, is_active = ?, require_password = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $description, $is_active, $require_password, $password, $id]);
            
            $success_message = '教室更新成功！' . ($require_password ? '（已啟用密碼保護）' : '');
            
        } elseif ($action === 'delete_classroom') {
            // 刪除教室
            $id = $_POST['classroom_id'];
            
            // 檢查是否有作業使用此教室
            $stmt = $db->prepare("SELECT COUNT(*) FROM assignments WHERE classroom_id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                throw new Exception('無法刪除：此教室還有 ' . $count . ' 個作業，請先移動或刪除這些作業');
            }
            
            $stmt = $db->prepare("DELETE FROM classrooms WHERE id = ?");
            $stmt->execute([$id]);
            
            $success_message = '教室刪除成功！';
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// 獲取所有教室
try {
    $db = getDB();
    $stmt = $db->query("
        SELECT c.*, 
               COUNT(a.id) as assignment_count 
        FROM classrooms c 
        LEFT JOIN assignments a ON c.id = a.classroom_id 
        GROUP BY c.id 
        ORDER BY c.created_at DESC
    ");
    $classrooms = $stmt->fetchAll();
} catch (Exception $e) {
    $error_message = "無法載入教室列表：" . $e->getMessage();
    $classrooms = [];
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>教室管理 - <?php echo htmlspecialchars($config['school_name'] ?? '學校'); ?></title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="checkbox-styles.css?v=<?php echo time(); ?>">
    <style>
        .classroom-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .classroom-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e1e5e9;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .classroom-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .classroom-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .classroom-name {
            font-size: 1.2em;
            font-weight: bold;
            color: #2c3e50;
            margin: 0;
        }
        
        .classroom-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .classroom-info {
            margin-bottom: 15px;
        }
        
        .share-code {
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-weight: bold;
            color: #495057;
            border: 1px solid #dee2e6;
            display: inline-block;
            margin: 5px 0;
        }
        
        .assignment-count {
            color: #6c757d;
            font-size: 0.9em;
            margin: 5px 0;
        }
        
        .classroom-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        
        @media (max-width: 480px) {
            .classroom-actions {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
            }
            
            .classroom-actions .btn-small {
                font-size: 0.75em;
                padding: 5px 8px;
            }
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 0.8em;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s ease;
        }
        
        .btn-edit {
            background: #007bff;
            color: white;
        }
        
        .btn-edit:hover {
            background: #0056b3;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
        }
        
        .btn-copy {
            background: #28a745;
            color: white;
        }
        
        .btn-copy:hover {
            background: #218838;
        }
        
        .btn-copy-link {
            background: #17a2b8;
            color: white;
        }
        
        .btn-copy-link:hover {
            background: #138496;
        }
        
        .btn-qr {
            background: #6f42c1;
            color: white;
        }
        
        .btn-qr:hover {
            background: #5a32a3;
        }
        
        .create-classroom-form {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .classroom-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .help-text {
            font-size: 0.85em;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }
        
        .close:hover {
            color: #000;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><?php echo htmlspecialchars($config['school_name'] ?? '學校'); ?></h1>
            <h2>🏫 教室管理</h2>
            <div class="header-actions">
                <a href="admin.php" class="btn-view">返回管理面板</a>
                <a href="?action=logout" class="btn-logout">登出</a>
            </div>
        </header>

        <?php if ($success_message): ?>
            <div class="alert success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <!-- 創建新教室表單 -->
        <section class="create-classroom-form">
            <h3>➕ 創建新教室</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create_classroom">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">教室名稱 *</label>
                        <input type="text" id="name" name="name" required placeholder="例如：高一甲班">
                        <div class="help-text">學生會看到的教室名稱</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="share_code">分享代碼 *</label>
                        <input type="text" id="share_code" name="share_code" required 
                               placeholder="例如：CLASS2025A" maxlength="20" 
                               pattern="[A-Za-z0-9]+" title="只能包含英文字母和數字">
                        <div class="help-text">學生用來加入教室的代碼，只能包含英文字母和數字</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">教室描述</label>
                    <textarea id="description" name="description" rows="3" 
                              placeholder="教室的詳細說明（可選）"></textarea>
                    <div class="help-text">可以包含課程資訊、作業要求等</div>
                </div>
                
                <div class="form-group">
                    <label class="custom-checkbox">
                        <input type="checkbox" id="require_password" name="require_password" 
                               onchange="togglePasswordField()">
                        <span class="checkmark"></span>
                        <span class="checkbox-label">啟用密碼保護</span>
                    </label>
                    <div class="help-text">啟用後，學生需要輸入密碼才能加入教室（分享連結除外）</div>
                </div>
                
                <div class="form-group" id="password_field" style="display: none;">
                    <label for="password">教室密碼</label>
                    <input type="text" id="password" name="password" 
                           placeholder="設定教室密碼">
                    <div class="help-text">學生加入教室時需要輸入此密碼</div>
                </div>
                
                <button type="submit" class="btn-submit">創建教室</button>
            </form>
            
            <div style="margin-top: 20px; padding: 15px; background: #d1ecf1; border-radius: 8px; border-left: 4px solid #17a2b8;">
                <h4 style="margin-top: 0; color: #0c5460;">💡 快速分享提示</h4>
                <p style="margin-bottom: 0; color: #0c5460;">
                    創建教室後，您可以使用以下方式快速分享給學生：<br>
                    📋 <strong>複製連結</strong> - 包含完整的使用說明文字<br>
                    📱 <strong>QR碼</strong> - 學生掃描即可直接上傳作業<br>
                    🔗 <strong>完整頁面</strong> - 可列印的QR碼頁面
                </p>
            </div>
        </section>

        <!-- 教室列表 -->
        <section>
            <h3>📚 現有教室 (<?php echo count($classrooms); ?>)</h3>
            
            <?php if (empty($classrooms)): ?>
                <div class="no-data">
                    <div class="no-data-icon">🏫</div>
                    <h4>還沒有創建任何教室</h4>
                    <p>創建第一個教室來開始管理作業吧！</p>
                </div>
            <?php else: ?>
                <div class="classroom-grid">
                    <?php foreach ($classrooms as $classroom): ?>
                        <div class="classroom-card">
                            <div class="classroom-header">
                                <h4 class="classroom-name"><?php echo htmlspecialchars($classroom['name']); ?></h4>
                                <span class="classroom-status <?php echo $classroom['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $classroom['is_active'] ? '啟用' : '停用'; ?>
                                </span>
                            </div>
                            
                            <div class="classroom-info">
                                <?php if ($classroom['description']): ?>
                                    <p><?php echo htmlspecialchars($classroom['description']); ?></p>
                                <?php endif; ?>
                                
                                <div>
                                    <strong>分享代碼：</strong>
                                    <span class="share-code" id="code-<?php echo $classroom['id']; ?>">
                                        <?php echo htmlspecialchars($classroom['share_code']); ?>
                                    </span>
                                </div>
                                
                                <div class="assignment-count">
                                    📝 作業數量：<?php echo $classroom['assignment_count']; ?>
                                </div>
                                
                                <div class="assignment-count">
                                    🔒 密碼保護：<?php echo $classroom['require_password'] ? '已啟用' : '未啟用'; ?>
                                    <?php if ($classroom['require_password']): ?>
                                        <span style="color: #6c757d;">（密碼：<?php echo htmlspecialchars($classroom['password']); ?>）</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="assignment-count">
                                    📅 創建時間：<?php echo date('Y-m-d H:i', strtotime($classroom['created_at'])); ?>
                                </div>
                            </div>
                            
                            <div class="classroom-actions">
                                <button class="btn-small btn-copy" 
                                        onclick="copyShareCode('<?php echo $classroom['share_code']; ?>', <?php echo $classroom['id']; ?>)">
                                    複製代碼
                                </button>
                                <button class="btn-small btn-copy-link" 
                                        onclick="copyShareLink('<?php echo $classroom['share_code']; ?>', '<?php echo htmlspecialchars($classroom['name']); ?>', <?php echo $classroom['id']; ?>)">
                                    複製連結
                                </button>
                                <button class="btn-small btn-qr" 
                                        onclick="showQRPreview('<?php echo $classroom['share_code']; ?>', '<?php echo htmlspecialchars($classroom['name']); ?>')">
                                    QR碼
                                </button>
                                <button class="btn-small btn-edit" 
                                        onclick="editClassroom(<?php echo $classroom['id']; ?>, '<?php echo htmlspecialchars($classroom['name']); ?>', '<?php echo htmlspecialchars($classroom['description']); ?>', <?php echo $classroom['is_active']; ?>, <?php echo $classroom['require_password']; ?>, '<?php echo htmlspecialchars($classroom['password'] ?? ''); ?>')">
                                    編輯
                                </button>
                                <?php if ($classroom['assignment_count'] == 0): ?>
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('確定要刪除教室「<?php echo htmlspecialchars($classroom['name']); ?>」嗎？')">
                                        <input type="hidden" name="action" value="delete_classroom">
                                        <input type="hidden" name="classroom_id" value="<?php echo $classroom['id']; ?>">
                                        <button type="submit" class="btn-small btn-delete">刪除</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- 編輯教室彈窗 -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>編輯教室</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_classroom">
                <input type="hidden" name="classroom_id" id="edit_classroom_id">
                
                <div class="form-group">
                    <label for="edit_name">教室名稱 *</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_description">教室描述</label>
                    <textarea id="edit_description" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="custom-checkbox">
                        <input type="checkbox" id="edit_is_active" name="is_active" value="1">
                        <span class="checkmark"></span>
                        <span class="checkbox-label">啟用此教室</span>
                    </label>
                    <div class="help-text">停用的教室將不能接受新的作業提交</div>
                </div>
                
                <div class="form-group">
                    <label class="custom-checkbox">
                        <input type="checkbox" id="edit_require_password" name="require_password" 
                               onchange="toggleEditPasswordField()">
                        <span class="checkmark"></span>
                        <span class="checkbox-label">啟用密碼保護</span>
                    </label>
                    <div class="help-text">啟用後，學生需要輸入密碼才能加入教室（分享連結除外）</div>
                </div>
                
                <div class="form-group" id="edit_password_field" style="display: none;">
                    <label for="edit_password">教室密碼</label>
                    <input type="text" id="edit_password" name="password" 
                           placeholder="設定教室密碼">
                    <div class="help-text">學生加入教室時需要輸入此密碼</div>
                </div>
                
                <button type="submit" class="btn-submit">更新教室</button>
            </form>
        </div>
    </div>

    <script>
        // 複製分享代碼
        function copyShareCode(code, classroomId) {
            navigator.clipboard.writeText(code).then(function() {
                // 顯示複製成功提示
                const element = document.getElementById('code-' + classroomId);
                const originalText = element.textContent;
                element.textContent = '已複製!';
                element.style.background = '#d4edda';
                element.style.color = '#155724';
                
                setTimeout(function() {
                    element.textContent = originalText;
                    element.style.background = '#f8f9fa';
                    element.style.color = '#495057';
                }, 2000);
            }).catch(function() {
                alert('複製失敗，請手動複製：' + code);
            });
        }

        // 複製教室連結（含引導文字）
        function copyShareLink(code, classroomName, classroomId) {
            const baseUrl = window.location.origin + window.location.pathname.replace('admin_classrooms.php', '');
            const shareUrl = baseUrl + 'upload.php?code=' + code;
            
            const guideText = `🏫 ${classroomName} - 作業上傳連結

📝 請點擊以下連結上傳您的作業：
${shareUrl}

💡 使用說明：
1. 點擊連結會自動加入教室
2. 填寫您的作業資訊
3. 點擊「上傳作業」完成提交

📞 如有問題請聯絡老師`;

            navigator.clipboard.writeText(guideText).then(function() {
                showCopySuccess(classroomId, '連結已複製！');
            }).catch(function() {
                // 降級處理：顯示文字讓用戶手動複製
                showCopyModal(guideText, classroomName);
            });
        }

        // 顯示複製成功提示
        function showCopySuccess(classroomId, message) {
            const element = document.getElementById('code-' + classroomId);
            const originalText = element.textContent;
            element.textContent = message;
            element.style.background = '#d4edda';
            element.style.color = '#155724';
            
            setTimeout(function() {
                element.textContent = originalText;
                element.style.background = '#f8f9fa';
                element.style.color = '#495057';
            }, 3000);
        }

        // 顯示QR碼預覽
        function showQRPreview(code, classroomName) {
            const baseUrl = window.location.origin + window.location.pathname.replace('admin_classrooms.php', '');
            const shareUrl = baseUrl + 'upload.php?code=' + code;
            const qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(shareUrl);
            
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'block';
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 400px;">
                    <span class="close" onclick="this.closest('.modal').remove()">&times;</span>
                    <h3>📱 ${classroomName} - QR碼</h3>
                    <div style="text-align: center; margin: 20px 0;">
                        <img src="${qrApiUrl}" alt="QR Code" style="border: 1px solid #ddd; border-radius: 8px;">
                    </div>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin: 15px 0;">
                        <strong>教室代碼：</strong>${code}<br>
                        <strong>上傳連結：</strong><br>
                        <small style="word-break: break-all;">${shareUrl}</small>
                    </div>
                    <div style="text-align: center;">
                        <button onclick="downloadQRFromPreview('${qrApiUrl}', '${classroomName}')" class="btn-submit" style="margin: 5px;">💾 下載QR碼</button>
                        <button onclick="openQRPage('${code}', '${classroomName}')" class="btn-submit" style="margin: 5px; background: #17a2b8;">🔗 完整頁面</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        // 從預覽下載QR碼
        function downloadQRFromPreview(qrUrl, classroomName) {
            const link = document.createElement('a');
            link.href = qrUrl;
            link.download = `${classroomName}_QR碼.png`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // 開啟QR碼完整頁面
        function openQRPage(code, classroomName) {
            const url = 'qr_generator.php?code=' + encodeURIComponent(code) + '&name=' + encodeURIComponent(classroomName);
            window.open(url, '_blank');
        }

        // 顯示複製文字的彈窗（當剪貼板API失敗時使用）
        function showCopyModal(text, classroomName) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'block';
            modal.innerHTML = `
                <div class="modal-content">
                    <span class="close" onclick="this.closest('.modal').remove()">&times;</span>
                    <h3>📋 複製教室連結 - ${classroomName}</h3>
                    <p>請手動複製以下文字：</p>
                    <textarea style="width: 100%; height: 200px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" readonly>${text}</textarea>
                    <br><br>
                    <button onclick="selectAndCopy(this.previousElementSibling.previousElementSibling)" class="btn-submit">選取並複製</button>
                </div>
            `;
            document.body.appendChild(modal);
        }

        // 選取並複製文字
        function selectAndCopy(textarea) {
            textarea.select();
            textarea.setSelectionRange(0, 99999); // 移動端支援
            try {
                document.execCommand('copy');
                alert('文字已複製到剪貼板！');
            } catch (err) {
                alert('複製失敗，請手動選取文字複製');
            }
        }

        // 編輯教室
        function editClassroom(id, name, description, isActive, requirePassword, password) {
            document.getElementById('edit_classroom_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_is_active').checked = isActive == 1;
            document.getElementById('edit_require_password').checked = requirePassword == 1;
            document.getElementById('edit_password').value = password || '';
            
            // 顯示或隱藏密碼欄位
            toggleEditPasswordField();
            
            document.getElementById('editModal').style.display = 'block';
        }

        // 切換密碼欄位顯示（創建）
        function togglePasswordField() {
            const checkbox = document.getElementById('require_password');
            const field = document.getElementById('password_field');
            const passwordInput = document.getElementById('password');
            
            if (checkbox.checked) {
                field.style.display = 'block';
                passwordInput.required = true;
            } else {
                field.style.display = 'none';
                passwordInput.required = false;
                passwordInput.value = '';
            }
        }

        // 切換密碼欄位顯示（編輯）
        function toggleEditPasswordField() {
            const checkbox = document.getElementById('edit_require_password');
            const field = document.getElementById('edit_password_field');
            const passwordInput = document.getElementById('edit_password');
            
            if (checkbox.checked) {
                field.style.display = 'block';
                passwordInput.required = true;
            } else {
                field.style.display = 'none';
                passwordInput.required = false;
                passwordInput.value = '';
            }
        }

        // 關閉彈窗
        document.querySelector('.close').onclick = function() {
            document.getElementById('editModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // 自動生成分享代碼建議
        document.getElementById('name').addEventListener('input', function() {
            const name = this.value.trim();
            if (name && !document.getElementById('share_code').value) {
                // 簡單的代碼生成邏輯
                let suggestion = name.replace(/[^a-zA-Z0-9\u4e00-\u9fa5]/g, '').toUpperCase();
                if (suggestion.length > 15) {
                    suggestion = suggestion.substring(0, 15);
                }
                suggestion += new Date().getFullYear().toString().slice(-2);
                document.getElementById('share_code').value = suggestion;
            }
        });
    </script>
</body>
</html>
