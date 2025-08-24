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

// 處理作業操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $db = getDB();
        
        if ($action === 'score_assignment') {
            // 評分作業
            $assignment_id = $_POST['assignment_id'];
            $score = $_POST['score'];
            $score_comment = trim($_POST['score_comment']);
            
            if ($score < 0 || $score > $config['max_score']) {
                throw new Exception('分數必須在 0 到 ' . $config['max_score'] . ' 之間');
            }
            
            $stmt = $db->prepare("UPDATE assignments SET score = ?, score_comment = ? WHERE id = ?");
            $stmt->execute([$score, $score_comment, $assignment_id]);
            
            $success_message = '評分完成！';
            
        } elseif ($action === 'update_public_status') {
            // 更新公開狀態
            $assignment_id = $_POST['assignment_id'];
            $is_public = isset($_POST['is_public']) ? 1 : 0;
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;
            
            $stmt = $db->prepare("UPDATE assignments SET is_public = ?, is_featured = ? WHERE id = ?");
            $stmt->execute([$is_public, $is_featured, $assignment_id]);
            
            $success_message = '公開狀態更新成功！';
            
        } elseif ($action === 'edit_assignment') {
            // 編輯作業
            $assignment_id = $_POST['assignment_id'];
            $group_name = trim($_POST['group_name']);
            $student_name = trim($_POST['student_name']);
            $title = trim($_POST['title']);
            $url = trim($_POST['url']);
            $classroom_id = $_POST['classroom_id'] ?: null;
            
            $stmt = $db->prepare("UPDATE assignments SET group_name = ?, student_name = ?, title = ?, url = ?, classroom_id = ?, edit_time = NOW() WHERE id = ?");
            $stmt->execute([$group_name, $student_name, $title, $url, $classroom_id, $assignment_id]);
            
            $success_message = '作業編輯成功！';
            
        } elseif ($action === 'delete_assignment') {
            // 刪除作業
            $assignment_id = $_POST['assignment_id'];
            
            $stmt = $db->prepare("DELETE FROM assignments WHERE id = ?");
            $stmt->execute([$assignment_id]);
            
            $success_message = '作業刪除成功！';
            
        } elseif ($action === 'toggle_public') {
            // 切換公開狀態
            $assignment_id = $_POST['assignment_id'];
            $is_public = $_POST['is_public'] ? 1 : 0;
            
            $stmt = $db->prepare("UPDATE assignments SET is_public = ? WHERE id = ?");
            $stmt->execute([$is_public, $assignment_id]);
            
            echo json_encode(['success' => true, 'message' => '狀態更新成功']);
            exit;
            
        } elseif ($action === 'toggle_featured') {
            // 切換精選狀態
            $assignment_id = $_POST['assignment_id'];
            $is_featured = $_POST['is_featured'] ? 1 : 0;
            
            $stmt = $db->prepare("UPDATE assignments SET is_featured = ? WHERE id = ?");
            $stmt->execute([$is_featured, $assignment_id]);
            
            echo json_encode(['success' => true, 'message' => '精選狀態更新成功']);
            exit;
            
        } elseif ($action === 'batch_operation') {
            // 批量操作
            $assignment_ids = $_POST['assignment_ids'] ?? [];
            $operation = $_POST['operation'] ?? '';
            
            if (empty($assignment_ids)) {
                throw new Exception('請選擇要操作的作業');
            }
            
            $placeholders = str_repeat('?,', count($assignment_ids) - 1) . '?';
            
            switch ($operation) {
                case 'make_public':
                    $stmt = $db->prepare("UPDATE assignments SET is_public = 1 WHERE id IN ($placeholders)");
                    $stmt->execute($assignment_ids);
                    $success_message = '已將 ' . count($assignment_ids) . ' 個作業設為公開';
                    break;
                    
                case 'make_private':
                    $stmt = $db->prepare("UPDATE assignments SET is_public = 0 WHERE id IN ($placeholders)");
                    $stmt->execute($assignment_ids);
                    $success_message = '已將 ' . count($assignment_ids) . ' 個作業設為不公開';
                    break;
                    
                case 'make_featured':
                    $stmt = $db->prepare("UPDATE assignments SET is_featured = 1 WHERE id IN ($placeholders)");
                    $stmt->execute($assignment_ids);
                    $success_message = '已將 ' . count($assignment_ids) . ' 個作業設為精選';
                    break;
                    
                case 'remove_featured':
                    $stmt = $db->prepare("UPDATE assignments SET is_featured = 0 WHERE id IN ($placeholders)");
                    $stmt->execute($assignment_ids);
                    $success_message = '已取消 ' . count($assignment_ids) . ' 個作業的精選狀態';
                    break;
                    
                case 'delete':
                    $stmt = $db->prepare("DELETE FROM assignments WHERE id IN ($placeholders)");
                    $stmt->execute($assignment_ids);
                    $success_message = '已刪除 ' . count($assignment_ids) . ' 個作業';
                    break;
                    
                default:
                    throw new Exception('無效的操作類型');
            }
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// 獲取篩選參數
$classroom_filter = $_GET['classroom'] ?? '';
$score_filter = $_GET['score'] ?? '';
$public_filter = $_GET['public'] ?? '';

// 構建查詢
$where_conditions = [];
$params = [];

if ($classroom_filter) {
    $where_conditions[] = "a.classroom_id = ?";
    $params[] = $classroom_filter;
}

if ($score_filter === 'scored') {
    $where_conditions[] = "a.score IS NOT NULL";
} elseif ($score_filter === 'unscored') {
    $where_conditions[] = "a.score IS NULL";
}

if ($public_filter === 'public') {
    $where_conditions[] = "a.is_public = 1";
} elseif ($public_filter === 'private') {
    $where_conditions[] = "a.is_public = 0";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// 獲取作業列表
try {
    $db = getDB();
    
    $sql = "
        SELECT a.*, c.name as classroom_name, c.share_code
        FROM assignments a 
        LEFT JOIN classrooms c ON a.classroom_id = c.id 
        $where_clause
        ORDER BY a.submit_time DESC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $assignments = $stmt->fetchAll();
    
    // 獲取教室列表
    $stmt = $db->query("SELECT id, name FROM classrooms WHERE is_active = 1 ORDER BY name");
    $classrooms = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error_message = "無法載入作業列表：" . $e->getMessage();
    $assignments = [];
    $classrooms = [];
}

// 統計資訊
$total_assignments = count($assignments);
$scored_count = count(array_filter($assignments, function($a) { return $a['score'] !== null; }));
$unscored_count = $total_assignments - $scored_count;
$featured_count = count(array_filter($assignments, function($a) { return $a['is_featured']; }));
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>作業管理 - <?php echo htmlspecialchars($config['school_name'] ?? '學校'); ?></title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="checkbox-styles.css?v=<?php echo time(); ?>">
    <style>
        .filters {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-group label {
            font-size: 0.9em;
            color: #666;
            font-weight: bold;
        }
        
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.9em;
        }
        
        .assignments-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: bold;
            color: #495057;
        }
        
        .score-input {
            width: 70px;
            padding: 4px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        
        .score-comment {
            width: 150px;
            padding: 4px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            min-height: 60px;
        }
        
        .showcase-select {
            padding: 4px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.8em;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: bold;
            text-align: center;
            display: inline-block;
            min-width: 60px;
        }
        
        .status-public { background: #d4edda; color: #155724; }
        .status-private { background: #f8d7da; color: #721c24; }
        
        .score-display {
            font-weight: bold;
            color: #28a745;
        }
        
        .no-score {
            color: #6c757d;
            font-style: italic;
        }
        
        .featured-star {
            color: #ffc107;
            font-size: 1.2em;
        }
        
        .btn-group {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .btn-tiny {
            padding: 4px 8px;
            font-size: 0.75em;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-score { background: #007bff; color: white; }
        .btn-edit { background: #28a745; color: white; }
        .btn-delete { background: #dc3545; color: white; }
        .btn-view { background: #17a2b8; color: white; }
        
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9em;
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
            max-width: 600px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            max-height: 80vh;
            overflow-y: auto;
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
        
        /* 導出功能樣式 */
        .export-section {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .export-section h3 {
            margin: 0 0 15px 0;
            color: #495057;
            font-size: 1.1em;
        }
        
        .export-controls {
            display: flex;
            gap: 20px;
            align-items: flex-end;
        }
        
        .export-options {
            display: flex;
            gap: 15px;
            flex: 1;
        }
        
        .export-actions {
            display: flex;
            gap: 10px;
        }
        
        .date-range {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .date-range input {
            width: 140px;
        }
        
        .date-range span {
            color: #6c757d;
            font-size: 0.9em;
        }
        
        .btn-export {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(40, 167, 69, 0.2);
        }
        
        .btn-export:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
        }
        
        .btn-preview {
            background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(23, 162, 184, 0.2);
        }
        
        .btn-preview:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(23, 162, 184, 0.3);
        }
        
        .export-preview {
            margin-top: 20px;
            padding: 15px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 6px;
        }
        
        .export-preview h4 {
            margin: 0 0 10px 0;
            color: #495057;
        }
        
        .preview-content {
            max-height: 300px;
            overflow-y: auto;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
        }
        
        .preview-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .table-responsive {
                font-size: 0.8em;
            }
            
            .score-comment {
                width: 100px;
                min-height: 40px;
            }
            
            .export-controls {
                flex-direction: column;
                gap: 15px;
            }
            
            .export-options {
                flex-direction: column;
                gap: 10px;
            }
            
            .export-actions {
                width: 100%;
            }
            
            .export-actions button {
                flex: 1;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><?php echo htmlspecialchars($config['school_name'] ?? '學校'); ?></h1>
            <h2>📝 作業管理</h2>
            <div class="header-actions">
                <a href="admin.php" class="btn-view">返回管理面板</a>
                <a href="admin_classrooms.php" class="btn-view">教室管理</a>
                <a href="?action=logout" class="btn-logout">登出</a>
            </div>
        </header>

        <?php if ($success_message): ?>
            <div class="alert success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <!-- 統計摘要 -->
        <div class="stats-summary">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_assignments; ?></div>
                <div class="stat-label">總作業數</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $scored_count; ?></div>
                <div class="stat-label">已評分</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $unscored_count; ?></div>
                <div class="stat-label">待評分</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $featured_count; ?></div>
                <div class="stat-label">精選作業</div>
            </div>
        </div>

        <!-- 篩選器 -->
        <div class="filters">
            <div class="filter-group">
                <label>教室篩選</label>
                <select id="classroom-filter" onchange="applyFilters()">
                    <option value="">所有教室</option>
                    <?php foreach ($classrooms as $classroom): ?>
                        <option value="<?php echo $classroom['id']; ?>" 
                                <?php echo $classroom_filter == $classroom['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($classroom['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label>評分狀態</label>
                <select id="score-filter" onchange="applyFilters()">
                    <option value="">全部</option>
                    <option value="scored" <?php echo $score_filter === 'scored' ? 'selected' : ''; ?>>已評分</option>
                    <option value="unscored" <?php echo $score_filter === 'unscored' ? 'selected' : ''; ?>>未評分</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>公開狀態</label>
                <select id="public-filter" onchange="applyFilters()">
                    <option value="">全部</option>
                    <option value="public" <?php echo $public_filter === 'public' ? 'selected' : ''; ?>>公開</option>
                    <option value="private" <?php echo $public_filter === 'private' ? 'selected' : ''; ?>>不公開</option>
                </select>
            </div>
            
            <button onclick="clearFilters()" class="btn-submit">清除篩選</button>
        </div>

        <!-- CSV導出功能 -->
        <div class="export-section">
            <h3>📊 數據導出</h3>
            <div class="export-controls">
                <div class="export-options">
                    <div class="filter-group">
                        <label>導出範圍</label>
                        <select id="export-range">
                            <option value="all">所有作業</option>
                            <option value="current">當前篩選結果</option>
                            <option value="selected">選中的作業</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>日期範圍</label>
                        <div class="date-range">
                            <input type="date" id="export-date-from" placeholder="開始日期">
                            <span>至</span>
                            <input type="date" id="export-date-to" placeholder="結束日期">
                        </div>
                    </div>
                    
                    <div class="filter-group">
                        <label>排序方式</label>
                        <select id="export-sort">
                            <option value="submit_time-DESC">提交時間 (新到舊)</option>
                            <option value="submit_time-ASC">提交時間 (舊到新)</option>
                            <option value="student_name-ASC">學生姓名 (A-Z)</option>
                            <option value="student_name-DESC">學生姓名 (Z-A)</option>
                            <option value="group_name-ASC">組別名稱 (A-Z)</option>
                            <option value="score-DESC">分數 (高到低)</option>
                            <option value="score-ASC">分數 (低到高)</option>
                            <option value="classroom_name-ASC">教室名稱 (A-Z)</option>
                        </select>
                    </div>
                </div>
                
                <div class="export-actions">
                    <button onclick="exportToCSV()" class="btn-export">
                        📥 導出 CSV
                    </button>
                    <button onclick="previewExport()" class="btn-preview">
                        👁️ 預覽數據
                    </button>
                </div>
            </div>
            
            <div id="export-preview" class="export-preview" style="display: none;">
                <h4>導出預覽</h4>
                <div id="preview-content"></div>
                <div class="preview-actions">
                    <button onclick="confirmExport()" class="btn-success">確認導出</button>
                    <button onclick="closePreview()" class="btn-secondary">取消</button>
                </div>
            </div>
        </div>

        <!-- 批量操作控制 -->
        <div class="batch-controls" id="batchControls" style="display: none;">
            <div class="batch-actions">
                <label class="custom-checkbox select-all-checkbox">
                    <input type="checkbox" id="selectAll">
                    <span class="checkmark"></span>
                    <span class="checkbox-label">全選</span>
                </label>
                
                <button class="btn btn-success" onclick="batchOperation('make_public')">設為公開</button>
                <button class="btn btn-secondary" onclick="batchOperation('make_private')">設為不公開</button>
                <button class="btn btn-warning" onclick="batchOperation('make_featured')">設為精選</button>
                <button class="btn btn-outline-warning" onclick="batchOperation('remove_featured')">取消精選</button>
                <button class="btn btn-danger" onclick="batchOperation('delete')">刪除選中</button>
                
                <span class="batch-info" id="batchInfo">已選擇 0 個作業</span>
            </div>
        </div>

        <!-- 作業列表 -->
        <div class="assignments-table">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th width="40">
                                <label class="custom-checkbox">
                                    <input type="checkbox" id="selectAllHeader">
                                    <span class="checkmark"></span>
                                </label>
                            </th>
                            <th>作業資訊</th>
                            <th>學生</th>
                            <th>教室</th>
                            <th>評分</th>
                            <th>公開狀態</th>
                            <th>精選</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($assignments)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px; color: #6c757d;">
                                    沒有找到符合條件的作業
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($assignments as $assignment): ?>
                                <tr class="assignment-row" data-assignment-id="<?php echo $assignment['id']; ?>">
                                    <td>
                                        <label class="custom-checkbox">
                                            <input type="checkbox" class="assignment-checkbox" value="<?php echo $assignment['id']; ?>">
                                            <span class="checkmark"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($assignment['title']); ?></strong>
                                        <?php if ($assignment['is_featured']): ?>
                                            <span class="featured-star" title="精選作業">⭐</span>
                                        <?php endif; ?>
                                        <br>
                                        <small style="color: #6c757d;">
                                            <?php echo date('Y-m-d H:i', strtotime($assignment['submit_time'])); ?>
                                        </small>
                                        <br>
                                        <a href="<?php echo htmlspecialchars($assignment['url']); ?>" target="_blank" 
                                           style="font-size: 0.8em; color: #007bff;">
                                            <?php echo htmlspecialchars($assignment['url']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($assignment['student_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($assignment['group_name']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($assignment['classroom_name']): ?>
                                            <?php echo htmlspecialchars($assignment['classroom_name']); ?><br>
                                            <small style="color: #6c757d;"><?php echo htmlspecialchars($assignment['share_code']); ?></small>
                                        <?php else: ?>
                                            <span style="color: #6c757d;">未分配</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($assignment['score'] !== null): ?>
                                            <div class="score-display"><?php echo $assignment['score']; ?> 分</div>
                                            <?php if ($assignment['score_comment']): ?>
                                                <small style="color: #6c757d;" title="<?php echo htmlspecialchars($assignment['score_comment']); ?>">
                                                    <?php echo mb_substr($assignment['score_comment'], 0, 20); ?>...
                                                </small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="no-score">未評分</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="status-toggle-container">
                                            <label class="toggle-switch">
                                                <input type="checkbox" 
                                                       <?php echo $assignment['is_public'] ? 'checked' : ''; ?>
                                                       onchange="togglePublicStatus('<?php echo $assignment['id']; ?>', this.checked)">
                                                <span class="toggle-slider"></span>
                                            </label>
                                            <span class="status-label <?php echo $assignment['is_public'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $assignment['is_public'] ? '公開' : '不公開'; ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="status-toggle-container featured-toggle">
                                            <label class="toggle-switch">
                                                <input type="checkbox" 
                                                       <?php echo $assignment['is_featured'] ? 'checked' : ''; ?>
                                                       onchange="toggleFeaturedStatus('<?php echo $assignment['id']; ?>', this.checked)">
                                                <span class="toggle-slider"></span>
                                            </label>
                                            <span class="status-label <?php echo $assignment['is_featured'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $assignment['is_featured'] ? '精選' : '一般'; ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn-tiny btn-score" 
                                                    onclick="showScoreModal('<?php echo $assignment['id']; ?>', '<?php echo htmlspecialchars($assignment['title']); ?>', <?php echo $assignment['score'] ?? 'null'; ?>, '<?php echo htmlspecialchars($assignment['score_comment'] ?? ''); ?>')">
                                                評分
                                            </button>
                                            <button class="btn-tiny btn-edit" 
                                                    onclick="showEditModal('<?php echo $assignment['id']; ?>', '<?php echo htmlspecialchars($assignment['group_name']); ?>', '<?php echo htmlspecialchars($assignment['student_name']); ?>', '<?php echo htmlspecialchars($assignment['title']); ?>', '<?php echo htmlspecialchars($assignment['url']); ?>', <?php echo $assignment['classroom_id'] ?? 'null'; ?>)">
                                                編輯
                                            </button>
                                            <a href="<?php echo htmlspecialchars($assignment['url']); ?>" target="_blank" 
                                               class="btn-tiny btn-view">查看</a>
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('確定要刪除這個作業嗎？')">
                                                <input type="hidden" name="action" value="delete_assignment">
                                                <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                                <button type="submit" class="btn-tiny btn-delete">刪除</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 評分彈窗 -->
    <div id="scoreModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>作業評分</h3>
            <form method="POST">
                <input type="hidden" name="action" value="score_assignment">
                <input type="hidden" name="assignment_id" id="score_assignment_id">
                
                <div class="form-group">
                    <label>作業標題</label>
                    <div id="score_assignment_title" style="font-weight: bold; color: #495057; padding: 8px; background: #f8f9fa; border-radius: 4px;"></div>
                </div>
                
                <div class="form-group">
                    <label for="score">分數 (0-<?php echo $config['max_score']; ?>)</label>
                    <input type="number" id="score" name="score" min="0" max="<?php echo $config['max_score']; ?>" step="0.5" required>
                </div>
                
                <?php if ($config['allow_score_comments']): ?>
                <div class="form-group">
                    <label for="score_comment">評分備註</label>
                    <textarea id="score_comment" name="score_comment" rows="4" 
                              placeholder="可以添加評分說明或建議（可選）"></textarea>
                </div>
                <?php endif; ?>
                
                <button type="submit" class="btn-submit">儲存評分</button>
            </form>
        </div>
    </div>

    <!-- 編輯作業彈窗 -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>編輯作業</h3>
            <form method="POST">
                <input type="hidden" name="action" value="edit_assignment">
                <input type="hidden" name="assignment_id" id="edit_assignment_id">
                
                <div class="form-group">
                    <label for="edit_group_name">組別</label>
                    <input type="text" id="edit_group_name" name="group_name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_student_name">學生姓名</label>
                    <input type="text" id="edit_student_name" name="student_name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_title">作業標題</label>
                    <input type="text" id="edit_title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_url">作業網址</label>
                    <input type="url" id="edit_url" name="url" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_classroom_id">所屬教室</label>
                    <select id="edit_classroom_id" name="classroom_id">
                        <option value="">未分配</option>
                        <?php foreach ($classrooms as $classroom): ?>
                            <option value="<?php echo $classroom['id']; ?>">
                                <?php echo htmlspecialchars($classroom['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn-submit">更新作業</button>
            </form>
        </div>
    </div>

    <!-- 公開設定彈窗 -->
    <div id="publicModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>公開設定</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_public_status">
                <input type="hidden" name="assignment_id" id="public_assignment_id">
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="is_public" name="is_public" value="1">
                        設為公開作業
                    </label>
                    <div class="help-text">
                        <strong>公開：</strong>作業會在首頁顯示，所有人都能看到<br>
                        <strong>不公開：</strong>作業不會在首頁顯示，只有管理員能在後台看到
                    </div>
                </div>
                
                <?php if ($config['enable_featured']): ?>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="public_is_featured" name="is_featured" value="1">
                        設為精選作業
                    </label>
                    <div class="help-text">精選作業會在首頁優先顯示（僅在公開時生效）</div>
                </div>
                <?php endif; ?>
                
                <button type="submit" class="btn-submit">更新設定</button>
            </form>
        </div>
    </div>

    <script>
        // 篩選功能
        function applyFilters() {
            const classroom = document.getElementById('classroom-filter').value;
            const score = document.getElementById('score-filter').value;
            const publicStatus = document.getElementById('public-filter').value;
            
            const params = new URLSearchParams();
            if (classroom) params.set('classroom', classroom);
            if (score) params.set('score', score);
            if (publicStatus) params.set('public', publicStatus);
            
            window.location.href = '?' + params.toString();
        }
        
        function clearFilters() {
            window.location.href = window.location.pathname;
        }

        // 批量選擇功能
        document.addEventListener('DOMContentLoaded', function() {
            const selectAllHeader = document.getElementById('selectAllHeader');
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.assignment-checkbox');
            const batchControls = document.getElementById('batchControls');
            const batchInfo = document.getElementById('batchInfo');

            // 同步兩個全選框
            if (selectAllHeader) {
                selectAllHeader.addEventListener('change', function() {
                    if (selectAll) selectAll.checked = this.checked;
                    toggleAllCheckboxes(this.checked);
                });
            }

            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    if (selectAllHeader) selectAllHeader.checked = this.checked;
                    toggleAllCheckboxes(this.checked);
                });
            }

            // 單個複選框變化
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateBatchControls);
            });

            function toggleAllCheckboxes(checked) {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = checked;
                    const row = checkbox.closest('.assignment-row');
                    if (checked) {
                        row.classList.add('selected');
                    } else {
                        row.classList.remove('selected');
                    }
                });
                updateBatchControls();
            }

            function updateBatchControls() {
                const selectedCheckboxes = document.querySelectorAll('.assignment-checkbox:checked');
                const selectedCount = selectedCheckboxes.length;
                
                if (selectedCount > 0) {
                    if (batchControls) batchControls.style.display = 'block';
                    if (batchInfo) batchInfo.textContent = `已選擇 ${selectedCount} 個作業`;
                } else {
                    if (batchControls) batchControls.style.display = 'none';
                }

                // 更新全選框狀態
                if (selectedCount === checkboxes.length && checkboxes.length > 0) {
                    if (selectAllHeader) {
                        selectAllHeader.indeterminate = false;
                        selectAllHeader.checked = true;
                    }
                    if (selectAll) {
                        selectAll.indeterminate = false;
                        selectAll.checked = true;
                    }
                } else if (selectedCount > 0) {
                    if (selectAllHeader) selectAllHeader.indeterminate = true;
                    if (selectAll) selectAll.indeterminate = true;
                } else {
                    if (selectAllHeader) {
                        selectAllHeader.indeterminate = false;
                        selectAllHeader.checked = false;
                    }
                    if (selectAll) {
                        selectAll.indeterminate = false;
                        selectAll.checked = false;
                    }
                }

                // 更新行樣式
                selectedCheckboxes.forEach(checkbox => {
                    checkbox.closest('.assignment-row').classList.add('selected');
                });
                
                document.querySelectorAll('.assignment-checkbox:not(:checked)').forEach(checkbox => {
                    checkbox.closest('.assignment-row').classList.remove('selected');
                });
            }
        });

        // 批量操作
        function batchOperation(operation) {
            const selectedCheckboxes = document.querySelectorAll('.assignment-checkbox:checked');
            const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.value);
            
            if (selectedIds.length === 0) {
                alert('請選擇要操作的作業');
                return;
            }

            let confirmMessage = '';
            switch (operation) {
                case 'make_public':
                    confirmMessage = `確定要將選中的 ${selectedIds.length} 個作業設為公開嗎？`;
                    break;
                case 'make_private':
                    confirmMessage = `確定要將選中的 ${selectedIds.length} 個作業設為不公開嗎？`;
                    break;
                case 'make_featured':
                    confirmMessage = `確定要將選中的 ${selectedIds.length} 個作業設為精選嗎？`;
                    break;
                case 'remove_featured':
                    confirmMessage = `確定要取消選中的 ${selectedIds.length} 個作業的精選狀態嗎？`;
                    break;
                case 'delete':
                    confirmMessage = `確定要刪除選中的 ${selectedIds.length} 個作業嗎？此操作無法撤銷！`;
                    break;
            }

            if (confirm(confirmMessage)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="batch_operation">
                    <input type="hidden" name="operation" value="${operation}">
                    ${selectedIds.map(id => `<input type="hidden" name="assignment_ids[]" value="${id}">`).join('')}
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // 切換公開狀態
        function togglePublicStatus(assignmentId, isPublic) {
            const formData = new FormData();
            formData.append('action', 'toggle_public');
            formData.append('assignment_id', assignmentId);
            formData.append('is_public', isPublic ? '1' : '0');

            fetch('admin_assignments.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 更新狀態標籤
                    const row = document.querySelector(`[data-assignment-id="${assignmentId}"]`);
                    const statusLabel = row.querySelector('.status-label');
                    statusLabel.textContent = isPublic ? '公開' : '不公開';
                    statusLabel.className = `status-label ${isPublic ? 'active' : 'inactive'}`;
                } else {
                    alert('操作失敗：' + data.message);
                    // 恢復開關狀態
                    const toggle = document.querySelector(`[data-assignment-id="${assignmentId}"] .toggle-switch input`);
                    toggle.checked = !isPublic;
                }
            })
            .catch(error => {
                alert('操作失敗，請重試');
                // 恢復開關狀態
                const toggle = document.querySelector(`[data-assignment-id="${assignmentId}"] .toggle-switch input`);
                toggle.checked = !isPublic;
            });
        }

        // 切換精選狀態
        function toggleFeaturedStatus(assignmentId, isFeatured) {
            const formData = new FormData();
            formData.append('action', 'toggle_featured');
            formData.append('assignment_id', assignmentId);
            formData.append('is_featured', isFeatured ? '1' : '0');

            fetch('admin_assignments.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 更新狀態標籤
                    const row = document.querySelector(`[data-assignment-id="${assignmentId}"]`);
                    const statusLabel = row.querySelector('.featured-toggle .status-label');
                    statusLabel.textContent = isFeatured ? '精選' : '一般';
                    statusLabel.className = `status-label ${isFeatured ? 'active' : 'inactive'}`;
                } else {
                    alert('操作失敗：' + data.message);
                    // 恢復開關狀態
                    const toggle = document.querySelector(`[data-assignment-id="${assignmentId}"] .featured-toggle .toggle-switch input`);
                    toggle.checked = !isFeatured;
                }
            })
            .catch(error => {
                alert('操作失敗，請重試');
                // 恢復開關狀態
                const toggle = document.querySelector(`[data-assignment-id="${assignmentId}"] .featured-toggle .toggle-switch input`);
                toggle.checked = !isFeatured;
            });
        }

        // 評分彈窗
        function showScoreModal(id, title, score, comment) {
            document.getElementById('score_assignment_id').value = id;
            document.getElementById('score_assignment_title').textContent = title;
            document.getElementById('score').value = score || '';
            document.getElementById('score_comment').value = comment || '';
            document.getElementById('scoreModal').style.display = 'block';
        }

        // 編輯彈窗
        function showEditModal(id, group, student, title, url, classroomId) {
            document.getElementById('edit_assignment_id').value = id;
            document.getElementById('edit_group_name').value = group;
            document.getElementById('edit_student_name').value = student;
            document.getElementById('edit_title').value = title;
            document.getElementById('edit_url').value = url;
            document.getElementById('edit_classroom_id').value = classroomId || '';
            document.getElementById('editModal').style.display = 'block';
        }

        // 公開設定彈窗
        function showPublicModal(id, isPublic, featured) {
            document.getElementById('public_assignment_id').value = id;
            document.getElementById('is_public').checked = isPublic;
            document.getElementById('public_is_featured').checked = featured;
            document.getElementById('publicModal').style.display = 'block';
        }

        // 關閉彈窗
        document.querySelectorAll('.close').forEach(function(closeBtn) {
            closeBtn.onclick = function() {
                this.closest('.modal').style.display = 'none';
            }
        });

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // CSV導出功能
        function exportToCSV() {
            const range = document.getElementById('export-range').value;
            const dateFrom = document.getElementById('export-date-from').value;
            const dateTo = document.getElementById('export-date-to').value;
            const sort = document.getElementById('export-sort').value;
            
            if (range === 'selected') {
                const selectedIds = getSelectedAssignmentIds();
                if (selectedIds.length === 0) {
                    alert('請先選擇要導出的作業');
                    return;
                }
            }
            
            // 構建導出URL
            const params = new URLSearchParams();
            
            // 根據導出範圍設置參數
            if (range === 'current') {
                // 使用當前篩選條件
                const classroomFilter = document.getElementById('classroom-filter').value;
                const publicFilter = document.getElementById('public-filter').value;
                const scoreFilter = document.getElementById('score-filter').value;
                
                if (classroomFilter) params.append('classroom_id', classroomFilter);
                if (publicFilter === 'public') params.append('is_public', '1');
                if (publicFilter === 'private') params.append('is_public', '0');
                // 評分篩選需要特殊處理
                
            } else if (range === 'selected') {
                // 導出選中的作業
                const selectedIds = getSelectedAssignmentIds();
                params.append('assignment_ids', selectedIds.join(','));
            }
            
            // 添加日期範圍
            if (dateFrom) params.append('date_from', dateFrom);
            if (dateTo) params.append('date_to', dateTo);
            
            // 添加排序
            if (sort) {
                const [sortBy, sortOrder] = sort.split('-');
                params.append('sort_by', sortBy);
                params.append('sort_order', sortOrder);
            }
            
            // 執行導出
            const url = 'export_assignments_csv.php?' + params.toString();
            window.open(url, '_blank');
        }

        function previewExport() {
            const range = document.getElementById('export-range').value;
            
            if (range === 'selected') {
                const selectedIds = getSelectedAssignmentIds();
                if (selectedIds.length === 0) {
                    alert('請先選擇要預覽的作業');
                    return;
                }
            }
            
            // 顯示預覽區域
            const preview = document.getElementById('export-preview');
            const content = document.getElementById('preview-content');
            
            // 模擬預覽內容（實際應用中可以通過AJAX獲取）
            let previewText = '導出預覽：\n\n';
            previewText += 'ID,提交者姓名,作業標題,作業內容,圖片路徑,提交時間,分數,評分備註,是否公開,是否精選,教室名稱,教室代碼\n';
            
            if (range === 'all') {
                previewText += '將導出所有作業數據...\n';
            } else if (range === 'current') {
                previewText += '將導出當前篩選結果...\n';
            } else if (range === 'selected') {
                const selectedIds = getSelectedAssignmentIds();
                previewText += `將導出選中的 ${selectedIds.length} 個作業...\n`;
            }
            
            content.textContent = previewText;
            preview.style.display = 'block';
        }

        function confirmExport() {
            exportToCSV();
            closePreview();
        }

        function closePreview() {
            document.getElementById('export-preview').style.display = 'none';
        }

        function getSelectedAssignmentIds() {
            const checkboxes = document.querySelectorAll('input[name="assignment_ids[]"]:checked');
            return Array.from(checkboxes).map(cb => cb.value);
        }
    </script>
</body>
</html>
