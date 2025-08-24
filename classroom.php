<?php
session_start();

// 載入配置助手
require_once 'config_helper.php';
require_once 'db_config.php';
$config = get_config();

// 獲取教室ID
$classroom_id = $_GET['id'] ?? null;
if (!$classroom_id) {
    header('Location: index.php');
    exit;
}

// 獲取學生識別cookie
$student_cookie = $_COOKIE['student_id'] ?? null;
if (!$student_cookie) {
    header('Location: index.php');
    exit;
}

// 檢查學生是否有權限訪問該教室
$has_access = false;
$classroom = null;
try {
    $db = getDB();
    
    // 獲取教室資訊
    $stmt = $db->prepare("SELECT * FROM classrooms WHERE id = ? AND is_active = 1");
    $stmt->execute([$classroom_id]);
    $classroom = $stmt->fetch();
    
    if (!$classroom) {
        header('Location: index.php');
        exit;
    }
    
    // 檢查訪問權限
    $stmt = $db->prepare("SELECT 1 FROM student_classroom_access WHERE student_cookie = ? AND classroom_id = ?");
    $stmt->execute([$student_cookie, $classroom_id]);
    $has_access = $stmt->fetch() !== false;
    
    if (!$has_access) {
        header('Location: index.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: index.php');
    exit;
}

// 從MySQL讀取該教室的作業資料 - 只顯示公開的作業
$assignments = [];
try {
    $stmt = $db->prepare("
        SELECT a.*, c.name as classroom_name 
        FROM assignments a 
        LEFT JOIN classrooms c ON a.classroom_id = c.id 
        WHERE a.classroom_id = ? AND a.is_public = 1 
        ORDER BY a.is_featured DESC, a.submit_time DESC
    ");
    $stmt->execute([$classroom_id]);
    $assignments = $stmt->fetchAll();
} catch (Exception $e) {
    $db_error = "數據庫連接失敗，請聯繫管理員";
}

// 處理編輯表單
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $edit_id = $_POST['edit_id'];
    $submitter_cookie = $_COOKIE['submitter_cookie'] ?? '';
    
    try {
        $stmt = $db->prepare("UPDATE assignments SET group_name = ?, student_name = ?, title = ?, url = ?, edit_time = NOW() WHERE id = ? AND submitter_cookie = ? AND classroom_id = ?");
        $stmt->execute([
            $_POST['group'],
            $_POST['name'],
            $_POST['title'],
            $_POST['url'],
            $edit_id,
            $submitter_cookie,
            $classroom_id
        ]);
        
        header('Location: classroom.php?id=' . $classroom_id . '&edited=1');
        exit;
    } catch (Exception $e) {
        $error_message = "編輯失敗：" . $e->getMessage();
    }
}

// 處理刪除
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $delete_id = $_POST['delete_id'];
    $submitter_cookie = $_COOKIE['submitter_cookie'] ?? '';
    
    try {
        $stmt = $db->prepare("DELETE FROM assignments WHERE id = ? AND submitter_cookie = ? AND classroom_id = ?");
        $stmt->execute([$delete_id, $submitter_cookie, $classroom_id]);
        
        header('Location: classroom.php?id=' . $classroom_id . '&deleted=1');
        exit;
    } catch (Exception $e) {
        $error_message = "刪除失敗：" . $e->getMessage();
    }
}

$submitter_cookie = $_COOKIE['submitter_cookie'] ?? '';

// 統計資訊
$total_assignments = count($assignments);
$total_groups = count(array_unique(array_column($assignments, 'group_name')));
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($classroom['name']); ?> - 作業展示</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .classroom-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .classroom-title {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .classroom-description {
            font-size: 1.1em;
            opacity: 0.9;
            margin-bottom: 20px;
        }
        
        .classroom-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .btn-classroom {
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.3);
            transition: all 0.3s ease;
            font-weight: bold;
        }
        
        .btn-classroom:hover {
            background: rgba(255,255,255,0.3);
            color: white;
        }
        
        .featured {
            border: 2px solid #ffc107 !important;
            background: linear-gradient(135deg, #fff9e6 0%, #ffffff 100%) !important;
        }
        
        .featured-star {
            color: #ffc107;
            font-size: 1.2em;
            margin-right: 5px;
        }
        
        .score-display {
            color: #28a745;
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .assignment-card.featured {
            position: relative;
            overflow: hidden;
        }
        
        .assignment-card.featured::before {
            content: "精選";
            position: absolute;
            top: 10px;
            right: -25px;
            background: #ffc107;
            color: #000;
            padding: 5px 30px;
            transform: rotate(45deg);
            font-size: 0.8em;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
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
        
        @media (max-width: 768px) {
            .classroom-title {
                font-size: 1.5em;
            }
            
            .classroom-actions {
                gap: 10px;
            }
            
            .btn-classroom {
                padding: 8px 16px;
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <nav class="breadcrumb">
            <a href="index.php">🏠 教室選擇</a> > 
            <span><?php echo htmlspecialchars($classroom['name']); ?></span>
        </nav>

        <div class="classroom-header">
            <h1 class="classroom-title">🏫 <?php echo htmlspecialchars($classroom['name']); ?></h1>
            <?php if ($classroom['description']): ?>
                <div class="classroom-description"><?php echo htmlspecialchars($classroom['description']); ?></div>
            <?php endif; ?>
            <div class="classroom-actions">
                <a href="upload.php?classroom_id=<?php echo $classroom_id; ?>" class="btn-classroom">📝 上傳作業</a>
                <a href="help.php" class="btn-classroom">❓ 使用說明</a>
                <a href="index.php" class="btn-classroom">🔄 切換教室</a>
            </div>
        </div>

        <?php if (isset($_GET['edited'])): ?>
            <div class="alert success">作業編輯成功！</div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert success">作業刪除成功！</div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <section class="assignments-list">
            <h3>📚 作業展示</h3>
            <?php if (empty($assignments)): ?>
                <div class="no-data">
                    <div class="no-data-icon">📚</div>
                    <h4>這個教室還沒有公開的作業</h4>
                    <p>等待管理員將作業設定為公開後，作業將會在這裡顯示。</p>
                    <a href="upload.php?classroom_id=<?php echo $classroom_id; ?>" class="btn-upload-first">立即上傳作業</a>
                </div>
            <?php else: ?>
                <div class="assignments-grid">
                    <?php foreach ($assignments as $assignment): ?>
                        <div class="assignment-card <?php echo $assignment['is_featured'] ? 'featured' : ''; ?>">
                            <div class="assignment-header">
                                <h4>
                                    <?php if ($assignment['is_featured']): ?>
                                        <span class="featured-star" title="精選作業">⭐</span>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($assignment['title']); ?>
                                </h4>
                                <span class="group-badge"><?php echo htmlspecialchars($assignment['group_name']); ?></span>
                            </div>
                            
                            <div class="assignment-info">
                                <p><strong>姓名：</strong><?php echo htmlspecialchars($assignment['student_name']); ?></p>
                                <p><strong>網址：</strong><a href="<?php echo htmlspecialchars($assignment['url']); ?>" target="_blank"><?php echo htmlspecialchars($assignment['url']); ?></a></p>
                                <p><strong>上傳時間：</strong><?php echo htmlspecialchars($assignment['submit_time']); ?></p>
                                <?php if (!empty($assignment['edit_time'])): ?>
                                    <p><strong>編輯時間：</strong><?php echo htmlspecialchars($assignment['edit_time']); ?></p>
                                <?php endif; ?>
                                
                                <?php if (($config['score_visibility'] ?? 'private') === 'public' && $assignment['score'] !== null): ?>
                                    <p><strong>評分：</strong><span class="score-display"><?php echo $assignment['score']; ?> 分</span></p>
                                    <?php if ($assignment['score_comment']): ?>
                                        <p><strong>評語：</strong><?php echo htmlspecialchars($assignment['score_comment']); ?></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>

                            <div class="assignment-visit">
                                <a href="<?php echo htmlspecialchars($assignment['url']); ?>" target="_blank" class="btn-visit">
                                    <span class="visit-icon">🚀</span>
                                    <span class="visit-text">在新分頁中開啟</span>
                                </a>
                            </div>

                            <?php if ($assignment['submitter_cookie'] === $submitter_cookie): ?>
                                <div class="assignment-actions">
                                    <button class="btn-edit" onclick="showEditForm('<?php echo $assignment['id']; ?>', '<?php echo htmlspecialchars($assignment['group_name']); ?>', '<?php echo htmlspecialchars($assignment['student_name']); ?>', '<?php echo htmlspecialchars($assignment['title']); ?>', '<?php echo htmlspecialchars($assignment['url']); ?>')">編輯</button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('確定要刪除這個作業嗎？')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="delete_id" value="<?php echo $assignment['id']; ?>">
                                        <button type="submit" class="btn-delete">刪除</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- 統計資訊 -->
        <section class="stats-section">
            <h3 class="stats-title">📊 教室統計</h3>
            <div class="stats-grid">
                <div class="stat-item" data-icon="📚">
                    <div class="stat-number"><?php echo $total_assignments; ?></div>
                    <div class="stat-label">總作業數</div>
                </div>
                <div class="stat-item" data-icon="👥">
                    <div class="stat-number"><?php echo $total_groups; ?></div>
                    <div class="stat-label">參與組別</div>
                </div>
                <div class="stat-item" data-icon="📅">
                    <div class="stat-number"><?php echo date('m/d'); ?></div>
                    <div class="stat-label">今日日期</div>
                </div>
            </div>
        </section>
    </div>

    <!-- 編輯表單彈窗 -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>編輯作業</h3>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="edit_id" id="edit_id">
                
                <div class="form-group">
                    <label for="edit_group">組別：</label>
                    <input type="text" id="edit_group" name="group" required>
                </div>

                <div class="form-group">
                    <label for="edit_name">姓名：</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="edit_title">網站標題：</label>
                    <input type="text" id="edit_title" name="title" required>
                </div>

                <div class="form-group">
                    <label for="edit_url">網站網址：</label>
                    <input type="url" id="edit_url" name="url" required>
                </div>

                <button type="submit" class="btn-submit">更新作業</button>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
