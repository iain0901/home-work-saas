<?php
session_start();

// 檢查管理員登入狀態
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    die('未授權訪問');
}

require_once 'db_config.php';

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 獲取篩選參數
    $classroom_id = $_GET['classroom_id'] ?? '';
    $is_public = $_GET['is_public'] ?? '';
    $is_featured = $_GET['is_featured'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    $sort_by = $_GET['sort_by'] ?? 'created_at';
    $sort_order = $_GET['sort_order'] ?? 'DESC';
    $assignment_ids = $_GET['assignment_ids'] ?? '';
    
    // 構建查詢
    $sql = "SELECT 
                a.id,
                a.student_name,
                a.group_name,
                a.title,
                a.url,
                a.submit_time,
                a.created_at,
                a.score,
                a.score_comment,
                a.is_public,
                a.is_featured,
                a.showcase_status,
                c.name as classroom_name,
                c.share_code as classroom_code
            FROM assignments a
            LEFT JOIN classrooms c ON a.classroom_id = c.id
            WHERE 1=1";
    
    $params = [];
    
    // 添加篩選條件
    if (!empty($assignment_ids)) {
        // 如果指定了作業ID列表，只導出這些作業
        $ids = explode(',', $assignment_ids);
        $ids = array_filter(array_map('intval', $ids)); // 確保是整數
        if (!empty($ids)) {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $sql .= " AND a.id IN ($placeholders)";
            $params = array_merge($params, $ids);
        }
    } else {
        // 正常篩選條件
        if (!empty($classroom_id)) {
            $sql .= " AND a.classroom_id = :classroom_id";
            $params['classroom_id'] = $classroom_id;
        }
        
        if ($is_public !== '') {
            $sql .= " AND a.is_public = :is_public";
            $params['is_public'] = (int)$is_public;
        }
        
        if ($is_featured !== '') {
            $sql .= " AND a.is_featured = :is_featured";
            $params['is_featured'] = (int)$is_featured;
        }
        
        if (!empty($date_from)) {
            $sql .= " AND DATE(a.created_at) >= :date_from";
            $params['date_from'] = $date_from;
        }
        
        if (!empty($date_to)) {
            $sql .= " AND DATE(a.created_at) <= :date_to";
            $params['date_to'] = $date_to;
        }
    }
    
    // 添加排序
    $allowed_sort_fields = ['id', 'student_name', 'group_name', 'title', 'submit_time', 'created_at', 'score', 'classroom_name'];
    $allowed_sort_orders = ['ASC', 'DESC'];
    
    if (in_array($sort_by, $allowed_sort_fields) && in_array($sort_order, $allowed_sort_orders)) {
        $sql .= " ORDER BY " . $sort_by . " " . $sort_order;
    } else {
        $sql .= " ORDER BY created_at DESC";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 設置CSV下載頭
    $filename = 'assignments_export_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // 輸出BOM以支持Excel正確顯示中文
    echo "\xEF\xBB\xBF";
    
    // 創建CSV輸出
    $output = fopen('php://output', 'w');
    
    // 寫入CSV標題行
    $headers = [
        'ID',
        '學生姓名',
        '組別名稱',
        '作業標題',
        '作業網址',
        '提交時間',
        '記錄時間',
        '分數',
        '評分備註',
        '是否公開',
        '是否精選',
        '審核狀態',
        '教室名稱',
        '教室代碼'
    ];
    
    fputcsv($output, $headers);
    
    // 寫入數據行
    foreach ($assignments as $assignment) {
        $row = [
            $assignment['id'],
            $assignment['student_name'],
            $assignment['group_name'],
            $assignment['title'],
            $assignment['url'],
            $assignment['submit_time'],
            $assignment['created_at'],
            $assignment['score'] ?? '',
            $assignment['score_comment'] ?? '',
            $assignment['is_public'] ? '是' : '否',
            $assignment['is_featured'] ? '是' : '否',
            $assignment['showcase_status'] ?? '',
            $assignment['classroom_name'] ?? '',
            $assignment['classroom_code'] ?? ''
        ];
        
        fputcsv($output, $row);
    }
    
    fclose($output);
    
} catch (PDOException $e) {
    http_response_code(500);
    die('數據庫錯誤：' . $e->getMessage());
} catch (Exception $e) {
    http_response_code(500);
    die('系統錯誤：' . $e->getMessage());
}
?>
