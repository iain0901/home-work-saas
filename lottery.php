<?php
session_start();

// 載入配置助手
require_once 'config_helper.php';
require_once 'db_config.php';
$config = get_config();

// 獲取所有教室的組別名單
$groups_by_classroom = [];
try {
    $db = getDB();
    $stmt = $db->query("
        SELECT c.name as classroom_name, c.id as classroom_id,
               GROUP_CONCAT(DISTINCT a.group_name ORDER BY a.group_name) as groups
        FROM classrooms c
        LEFT JOIN assignments a ON c.id = a.classroom_id
        WHERE c.is_active = 1 AND a.group_name IS NOT NULL
        GROUP BY c.id, c.name
        ORDER BY c.name
    ");
    $results = $stmt->fetchAll();
    
    foreach ($results as $row) {
        if ($row['groups']) {
            $groups_by_classroom[$row['classroom_name']] = explode(',', $row['groups']);
        }
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
    <title>🎲 抽獎系統 - <?php echo htmlspecialchars($config['school_name']); ?></title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="checkbox-styles.css?v=<?php echo time(); ?>">
    <style>
        .lottery-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .lottery-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .lottery-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .tab-button {
            padding: 12px 20px;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .tab-button.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .tab-button:hover {
            background: #e9ecef;
        }
        
        .tab-button.active:hover {
            background: #0056b3;
        }
        
        .tab-content {
            display: none;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-section {
            margin-bottom: 25px;
        }
        
        .form-section h4 {
            margin-bottom: 15px;
            color: #2c3e50;
            font-size: 1.1em;
        }
        
        .input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .input-group input {
            flex: 1;
            min-width: 200px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #000;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .list-preview {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 15px;
            margin-top: 15px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .list-item {
            display: inline-block;
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 8px;
            margin: 2px;
            border-radius: 4px;
            font-size: 0.9em;
        }
        
        .lottery-wheel {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .wheel-container {
            position: relative;
            margin: 30px auto;
            width: 350px;
            height: 350px;
            perspective: 1000px;
        }
        
        .wheel {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 10px solid #007bff;
            background: conic-gradient(
                #ff6b6b 0deg 45deg,
                #4ecdc4 45deg 90deg,
                #45b7d1 90deg 135deg,
                #96ceb4 135deg 180deg,
                #ffeaa7 180deg 225deg,
                #dda0dd 225deg 270deg,
                #98d8c8 270deg 315deg,
                #f7dc6f 315deg 360deg
            );
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3em;
            font-weight: bold;
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.7);
            transition: transform 4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative;
            box-shadow: 
                0 0 30px rgba(0, 123, 255, 0.3),
                inset 0 0 50px rgba(255, 255, 255, 0.1);
            transform-style: preserve-3d;
        }
        
        .wheel.spinning {
            animation: wheelGlow 0.5s ease-in-out infinite alternate;
        }
        
        .wheel:hover:not(.spinning) {
            transform: scale(1.02);
            box-shadow: 
                0 0 40px rgba(0, 123, 255, 0.5),
                inset 0 0 50px rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .wheel:active:not(.spinning) {
            transform: scale(0.98);
            transition: all 0.1s ease;
        }
        
        @keyframes wheelGlow {
            from {
                box-shadow: 
                    0 0 30px rgba(0, 123, 255, 0.3),
                    inset 0 0 50px rgba(255, 255, 255, 0.1);
            }
            to {
                box-shadow: 
                    0 0 50px rgba(0, 123, 255, 0.6),
                    inset 0 0 50px rgba(255, 255, 255, 0.2);
            }
        }
        
        .wheel::before {
            content: '';
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 18px solid transparent;
            border-right: 18px solid transparent;
            border-top: 25px solid #dc3545;
            z-index: 10;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
        }
        
        .wheel::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 40px;
            height: 40px;
            background: radial-gradient(circle, #fff 30%, #ddd 100%);
            border-radius: 50%;
            border: 3px solid #007bff;
            z-index: 15;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        
        .wheel-text {
            position: relative;
            z-index: 20;
            padding: 20px;
            text-align: center;
            background: rgba(0,0,0,0.3);
            border-radius: 15px;
            backdrop-filter: blur(5px);
            border: 2px solid rgba(255,255,255,0.2);
            min-width: 200px;
            word-wrap: break-word;
        }
        
        .confetti-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
        }
        
        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background: #ff6b6b;
            animation: confetti-fall 3s linear infinite;
        }
        
        .confetti:nth-child(2n) { background: #4ecdc4; animation-delay: 0.5s; }
        .confetti:nth-child(3n) { background: #45b7d1; animation-delay: 1s; }
        .confetti:nth-child(4n) { background: #ffeaa7; animation-delay: 1.5s; }
        .confetti:nth-child(5n) { background: #dda0dd; animation-delay: 2s; }
        
        @keyframes confetti-fall {
            0% {
                transform: translateY(-100vh) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(100vh) rotate(720deg);
                opacity: 0;
            }
        }
        
        .wheel-glow {
            position: absolute;
            top: -20px;
            left: -20px;
            right: -20px;
            bottom: -20px;
            border-radius: 50%;
            background: radial-gradient(circle, transparent 60%, rgba(0, 123, 255, 0.1) 70%, transparent 80%);
            animation: glow-pulse 2s ease-in-out infinite;
            pointer-events: none;
        }
        
        @keyframes glow-pulse {
            0%, 100% { opacity: 0.5; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.05); }
        }
        
        .result-display {
            margin: 30px 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            font-size: 1.5em;
            font-weight: bold;
            text-align: center;
            min-height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .lottery-controls {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin: 20px 0;
        }
        
        .history-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-top: 30px;
        }
        
        .history-list {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 10px;
        }
        
        .history-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f1f3f4;
        }
        
        .history-item:last-child {
            border-bottom: none;
        }
        
        .classroom-select {
            margin-bottom: 20px;
        }
        
        .classroom-select select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: white;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 6px;
            margin: 10px 0;
            border: 1px solid #f5c6cb;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 6px;
            margin: 10px 0;
            border: 1px solid #c3e6cb;
        }
        
        .result-display.winner {
            animation: resultPulse 0.8s ease-in-out;
            background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%) !important;
        }
        
        @keyframes resultPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .wheel-sound-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.8em;
            transition: all 0.3s ease;
        }
        
        .wheel-sound-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .spinning-text {
            animation: spinningText 0.5s ease-in-out infinite alternate;
        }
        
        @keyframes spinningText {
            from { opacity: 0.7; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1.05); }
        }
        
        .fireworks {
            position: absolute;
            width: 4px;
            height: 4px;
            border-radius: 50%;
            animation: firework 1s ease-out forwards;
        }
        
        @keyframes firework {
            0% {
                transform: translate(0, 0) scale(1);
                opacity: 1;
            }
            100% {
                transform: translate(var(--dx), var(--dy)) scale(0);
                opacity: 0;
            }
        }
        
        @media (max-width: 768px) {
            .lottery-tabs {
                flex-direction: column;
            }
            
            .tab-button {
                text-align: center;
            }
            
            .input-group {
                flex-direction: column;
                align-items: stretch;
            }
            
            .input-group input {
                min-width: auto;
            }
            
            .lottery-controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .wheel-container {
                width: 280px;
                height: 280px;
            }
        }
    </style>
</head>
<body>
    <div class="lottery-container">
        <nav class="breadcrumb">
            <a href="index.php">🏠 首頁</a> > 
            <span>🎲 抽獎系統</span>
        </nav>

        <div class="lottery-header">
            <h1>🎲 抽獎系統</h1>
            <p>公平、透明、有趣的抽獎工具</p>
        </div>

        <!-- 標籤頁導航 -->
        <div class="lottery-tabs">
            <div class="tab-button active" onclick="switchTab('import')">
                📥 導入組別
            </div>
            <div class="tab-button" onclick="switchTab('manual')">
                ✏️ 手動建立
            </div>
            <div class="tab-button" onclick="switchTab('range')">
                🔢 數字範圍
            </div>
            <div class="tab-button" onclick="switchTab('lottery')">
                🎲 開始抽獎
            </div>
        </div>

        <!-- 導入組別標籤頁 -->
        <div id="import-tab" class="tab-content active">
            <h3>📥 導入教室組別名單</h3>
            
            <div class="classroom-select">
                <label for="classroom-select"><strong>選擇教室：</strong></label>
                <select id="classroom-select" onchange="loadClassroomGroups()">
                    <option value="">請選擇教室</option>
                    <?php foreach ($groups_by_classroom as $classroom => $groups): ?>
                        <option value="<?php echo htmlspecialchars($classroom); ?>">
                            <?php echo htmlspecialchars($classroom); ?> (<?php echo count($groups); ?> 組)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="groups-preview" class="list-preview" style="display: none;">
                <h4>可用組別：</h4>
                <div style="margin-bottom: 15px;">
                    <label class="custom-checkbox">
                        <input type="checkbox" id="select-all-groups" onchange="toggleAllGroups()">
                        <span class="checkmark"></span>
                        <span class="checkbox-label"><strong>全選</strong></span>
                    </label>
                </div>
                <div id="groups-list"></div>
                <div style="margin-top: 15px;">
                    <button class="btn btn-success" onclick="importSelectedGroups()">
                        📥 導入選中組別
                    </button>
                    <span id="selected-count" style="margin-left: 15px; color: #6c757d; font-size: 0.9em;">
                        已選擇 0 個組別
                    </span>
                </div>
            </div>

            <script>
                const groupsData = <?php echo json_encode($groups_by_classroom); ?>;
            </script>
        </div>

        <!-- 手動建立標籤頁 -->
        <div id="manual-tab" class="tab-content">
            <h3>✏️ 手動建立名單</h3>
            
            <div class="form-section">
                <h4>添加項目</h4>
                <div class="input-group">
                    <input type="text" id="manual-item" placeholder="輸入名稱（例如：第一組、小明）" 
                           onkeypress="if(event.key==='Enter') addManualItem()">
                    <button class="btn btn-primary" onclick="addManualItem()">
                        ➕ 添加
                    </button>
                </div>
            </div>

            <div class="form-section">
                <h4>批量添加</h4>
                <textarea id="manual-batch" rows="4" placeholder="每行一個項目，例如：&#10;第一組&#10;第二組&#10;第三組" 
                          style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;"></textarea>
                <div style="margin-top: 10px;">
                    <button class="btn btn-primary" onclick="addBatchItems()">
                        📝 批量添加
                    </button>
                </div>
            </div>

            <div id="manual-preview" class="list-preview">
                <h4>當前名單：</h4>
                <div id="manual-list"></div>
            </div>
        </div>

        <!-- 數字範圍標籤頁 -->
        <div id="range-tab" class="tab-content">
            <h3>🔢 數字範圍抽獎</h3>
            
            <div class="form-section">
                <h4>設定數字範圍</h4>
                <div class="input-group">
                    <label>起始數字：</label>
                    <input type="number" id="range-start" value="1" min="1">
                    <label>結束數字：</label>
                    <input type="number" id="range-end" value="10" min="1">
                    <button class="btn btn-primary" onclick="generateNumberRange()">
                        🔢 生成範圍
                    </button>
                </div>
            </div>

            <div class="form-section">
                <h4>或者自定義數字</h4>
                <div class="input-group">
                    <input type="text" id="custom-numbers" placeholder="輸入數字，用逗號分隔（例如：1,5,8,10,15）">
                    <button class="btn btn-primary" onclick="generateCustomNumbers()">
                        📝 生成自定義
                    </button>
                </div>
            </div>

            <div id="range-preview" class="list-preview">
                <h4>數字範圍：</h4>
                <div id="range-list"></div>
            </div>
        </div>

        <!-- 抽獎標籤頁 -->
        <div id="lottery-tab" class="tab-content">
            <div class="lottery-wheel">
                <h3>🎲 抽獎轉盤</h3>
                <button class="wheel-sound-btn" onclick="toggleSound()" id="sound-btn">
                    🔊 音效
                </button>
                
                <div class="wheel-container">
                    <div class="wheel-glow"></div>
                    <div class="wheel" id="lottery-wheel" onclick="startLottery()" style="cursor: pointer;">
                        <div class="wheel-text" id="wheel-text">
                            點擊開始抽獎
                        </div>
                    </div>
                    <div class="confetti-container" id="confetti-container"></div>
                </div>

                <div class="result-display" id="result-display">
                    🎯 抽獎結果將顯示在這裡
                </div>

                <div class="lottery-settings" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                    <h4 style="margin-bottom: 15px;">⚙️ 抽獎設定</h4>
                    <div class="setting-row">
                        <label class="custom-checkbox">
                            <input type="checkbox" id="allow-repeat" checked onchange="updateRepeatSetting()">
                            <span class="checkmark"></span>
                            <span class="checkbox-label">允許重複抽中</span>
                        </label>
                        <div class="help-text" style="margin-left: 30px; color: #6c757d; font-size: 0.85em;">
                            關閉後，已抽中的項目不會再次被抽中
                        </div>
                    </div>
                    <div id="drawn-items-info" style="margin-top: 10px; padding: 8px; background: #e3f2fd; border-radius: 4px; font-size: 0.9em; display: none;">
                        <span id="drawn-count">0</span> 個項目已被抽中，剩餘 <span id="remaining-count">0</span> 個項目可抽
                    </div>
                </div>

                <div class="lottery-controls">
                    <button class="btn btn-success" onclick="startLottery()" id="start-btn">
                        🎲 開始抽獎
                    </button>
                    <button class="btn btn-warning" onclick="resetLottery()">
                        🔄 重置
                    </button>
                    <button class="btn btn-danger" onclick="clearHistory()">
                        🗑️ 清除歷史
                    </button>
                    <button class="btn btn-info" onclick="resetDrawnItems()" id="reset-drawn-btn" style="display: none;">
                        🔄 重置已抽項目
                    </button>
                </div>

                <div class="form-section">
                    <h4>當前抽獎名單：</h4>
                    <div id="current-list" class="list-preview">
                        <em>請先在其他標籤頁建立名單</em>
                    </div>
                </div>
            </div>
        </div>

        <!-- 抽獎歷史 -->
        <div class="history-section">
            <h3>📜 抽獎歷史</h3>
            <div id="history-list" class="history-list">
                <em>暫無抽獎記錄</em>
            </div>
        </div>
    </div>

    <script>
        // 全局變量
        let currentList = [];
        let lotteryHistory = [];
        let drawnItems = [];
        let allowRepeat = true;
        let isSpinning = false;
        let soundEnabled = true;

        // 頁面加載時初始化
        document.addEventListener('DOMContentLoaded', function() {
            loadFromCookies();
            updateCurrentList();
            updateHistory();
        });

        // 標籤頁切換
        function switchTab(tabName) {
            // 隱藏所有標籤內容
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // 移除所有按鈕的活動狀態
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // 顯示選中的標籤
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
            
            // 更新抽獎頁面的當前名單
            if (tabName === 'lottery') {
                updateCurrentList();
            }
        }

        // 載入教室組別
        function loadClassroomGroups() {
            const classroom = document.getElementById('classroom-select').value;
            const previewDiv = document.getElementById('groups-preview');
            const groupsList = document.getElementById('groups-list');
            const selectAllCheckbox = document.getElementById('select-all-groups');
            
            if (classroom && groupsData[classroom]) {
                const groups = groupsData[classroom];
                groupsList.innerHTML = groups.map(group => 
                    `<label class="custom-checkbox list-item">
                        <input type="checkbox" value="${group}" class="group-checkbox" onchange="updateGroupSelection()">
                        <span class="checkmark"></span>
                        <span class="checkbox-label">${group}</span>
                    </label>`
                ).join('');
                previewDiv.style.display = 'block';
                selectAllCheckbox.checked = false;
                updateGroupSelection();
            } else {
                previewDiv.style.display = 'none';
            }
        }

        // 全選/取消全選組別
        function toggleAllGroups() {
            const selectAllCheckbox = document.getElementById('select-all-groups');
            const groupCheckboxes = document.querySelectorAll('.group-checkbox');
            
            groupCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            
            updateGroupSelection();
        }

        // 更新組別選擇狀態
        function updateGroupSelection() {
            const groupCheckboxes = document.querySelectorAll('.group-checkbox');
            const selectedCheckboxes = document.querySelectorAll('.group-checkbox:checked');
            const selectAllCheckbox = document.getElementById('select-all-groups');
            const selectedCount = document.getElementById('selected-count');
            
            // 更新計數顯示
            selectedCount.textContent = `已選擇 ${selectedCheckboxes.length} 個組別`;
            
            // 更新全選框狀態
            if (selectedCheckboxes.length === 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            } else if (selectedCheckboxes.length === groupCheckboxes.length) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            }
        }

        // 導入選中的組別
        function importSelectedGroups() {
            const checkboxes = document.querySelectorAll('.group-checkbox:checked');
            const selectedGroups = Array.from(checkboxes).map(cb => cb.value);
            
            if (selectedGroups.length === 0) {
                alert('請選擇要導入的組別');
                return;
            }
            
            currentList = selectedGroups;
            saveToCookies();
            updateCurrentList();
            
            // 顯示成功消息
            const classroom = document.getElementById('classroom-select').value;
            showMessage(`成功導入 ${classroom} 的 ${selectedGroups.length} 個組別`, 'success');
            
            // 自動切換到抽獎頁面
            setTimeout(() => {
                switchTab('lottery');
                document.querySelector('[onclick="switchTab(\'lottery\')"]').classList.add('active');
            }, 1000);
        }

        // 添加手動項目
        function addManualItem() {
            const input = document.getElementById('manual-item');
            const item = input.value.trim();
            
            if (item && !currentList.includes(item)) {
                currentList.push(item);
                input.value = '';
                updateManualPreview();
                saveToCookies();
            } else if (currentList.includes(item)) {
                alert('該項目已存在');
            }
        }

        // 批量添加項目
        function addBatchItems() {
            const textarea = document.getElementById('manual-batch');
            const items = textarea.value.split('\n')
                .map(item => item.trim())
                .filter(item => item && !currentList.includes(item));
            
            if (items.length > 0) {
                currentList.push(...items);
                textarea.value = '';
                updateManualPreview();
                saveToCookies();
                showMessage(`成功添加 ${items.length} 個項目`, 'success');
            }
        }

        // 更新手動名單預覽
        function updateManualPreview() {
            const listDiv = document.getElementById('manual-list');
            if (currentList.length === 0) {
                listDiv.innerHTML = '<em>名單為空</em>';
            } else {
                listDiv.innerHTML = currentList.map((item, index) => 
                    `<span class="list-item">
                        ${item}
                        <button onclick="removeItem(${index})" style="margin-left: 5px; background: #dc3545; color: white; border: none; border-radius: 3px; padding: 2px 5px; cursor: pointer;">✕</button>
                    </span>`
                ).join('');
            }
        }

        // 移除項目
        function removeItem(index) {
            currentList.splice(index, 1);
            updateManualPreview();
            saveToCookies();
        }

        // 生成數字範圍
        function generateNumberRange() {
            const start = parseInt(document.getElementById('range-start').value);
            const end = parseInt(document.getElementById('range-end').value);
            
            if (start >= end) {
                alert('結束數字必須大於起始數字');
                return;
            }
            
            if (end - start > 1000) {
                alert('範圍不能超過1000個數字');
                return;
            }
            
            currentList = [];
            for (let i = start; i <= end; i++) {
                currentList.push(i.toString());
            }
            
            updateRangePreview();
            saveToCookies();
            showMessage(`生成了 ${currentList.length} 個數字`, 'success');
        }

        // 生成自定義數字
        function generateCustomNumbers() {
            const input = document.getElementById('custom-numbers').value;
            const numbers = input.split(',')
                .map(num => num.trim())
                .filter(num => num && !isNaN(num))
                .map(num => parseInt(num).toString());
            
            if (numbers.length === 0) {
                alert('請輸入有效的數字');
                return;
            }
            
            // 去重
            currentList = [...new Set(numbers)];
            updateRangePreview();
            saveToCookies();
            showMessage(`生成了 ${currentList.length} 個數字`, 'success');
        }

        // 更新數字範圍預覽
        function updateRangePreview() {
            const listDiv = document.getElementById('range-list');
            if (currentList.length === 0) {
                listDiv.innerHTML = '<em>請先生成數字範圍</em>';
            } else {
                listDiv.innerHTML = currentList.map(item => 
                    `<span class="list-item">${item}</span>`
                ).join('');
            }
        }

        // 更新當前抽獎名單
        function updateCurrentList() {
            const listDiv = document.getElementById('current-list');
            if (currentList.length === 0) {
                listDiv.innerHTML = '<em>請先在其他標籤頁建立名單</em>';
            } else {
                const availableCount = allowRepeat ? currentList.length : currentList.length - drawnItems.length;
                listDiv.innerHTML = `
                    <div style="margin-bottom: 10px;">
                        <strong>共 ${currentList.length} 個項目</strong>
                        ${!allowRepeat ? `（可抽 ${availableCount} 個）` : ''}：
                    </div>
                    ${currentList.map(item => {
                        const isDrawn = drawnItems.includes(item);
                        const itemClass = isDrawn && !allowRepeat ? 'list-item' : 'list-item';
                        const itemStyle = isDrawn && !allowRepeat ? 'opacity: 0.5; text-decoration: line-through;' : '';
                        const drawnMark = isDrawn && !allowRepeat ? ' ✓' : '';
                        return `<span class="${itemClass}" style="${itemStyle}">${item}${drawnMark}</span>`;
                    }).join('')}
                    ${!allowRepeat && drawnItems.length > 0 ? 
                        '<div style="margin-top: 10px; font-size: 0.85em; color: #6c757d;">✓ 標記表示已抽中的項目</div>' : 
                        ''
                    }
                `;
            }
            updateDrawnItemsInfo();
        }

        // 開始抽獎
        function startLottery() {
            if (currentList.length === 0) {
                alert('請先建立抽獎名單');
                return;
            }
            
            // 獲取可抽獎的名單
            let availableList = currentList;
            if (!allowRepeat) {
                availableList = currentList.filter(item => !drawnItems.includes(item));
                if (availableList.length === 0) {
                    alert('所有項目都已被抽中！請重置已抽項目或開啟重複抽獎。');
                    return;
                }
            }
            
            if (isSpinning) {
                return;
            }
            
            isSpinning = true;
            const wheel = document.getElementById('lottery-wheel');
            const wheelText = document.getElementById('wheel-text');
            const resultDisplay = document.getElementById('result-display');
            const startBtn = document.getElementById('start-btn');
            
            // 開始動畫
            startBtn.disabled = true;
            startBtn.textContent = '抽獎中...';
            resultDisplay.textContent = '🎲 抽獎進行中...';
            resultDisplay.classList.remove('winner');
            
            // 禁用轉盤點擊
            wheel.style.cursor = 'not-allowed';
            wheel.style.pointerEvents = 'none';
            
            // 添加旋轉效果
            wheel.classList.add('spinning');
            wheelText.classList.add('spinning-text');
            wheelText.textContent = '抽獎中...';
            
            // 播放音效
            if (soundEnabled) {
                playSpinSound();
            }
            
            // 從可用名單中隨機選擇結果
            const randomIndex = Math.floor(Math.random() * availableList.length);
            const result = availableList[randomIndex];
            
            // 隨機名單滾動效果
            let rollCount = 0;
            const rollInterval = setInterval(() => {
                const randomItem = availableList[Math.floor(Math.random() * availableList.length)];
                wheelText.textContent = randomItem;
                rollCount++;
                
                if (rollCount > 20) {
                    clearInterval(rollInterval);
                }
            }, 100);
            
            // 轉盤動畫
            const rotations = 6 + Math.random() * 4; // 6-10圈
            const finalAngle = rotations * 360 + Math.random() * 360;
            wheel.style.transform = `rotate(${finalAngle}deg)`;
            
            // 4秒後顯示結果
            setTimeout(() => {
                // 停止動畫
                wheel.classList.remove('spinning');
                wheelText.classList.remove('spinning-text');
                clearInterval(rollInterval);
                
                // 顯示結果
                wheelText.textContent = result;
                resultDisplay.innerHTML = `🎉 恭喜：<strong>${result}</strong>`;
                resultDisplay.classList.add('winner');
                
                // 播放勝利音效
                if (soundEnabled) {
                    playWinSound();
                }
                
                // 創建彩帶效果
                createConfetti();
                
                // 創建煙花效果
                createFireworks();
                
                // 如果不允許重複，將結果添加到已抽項目
                if (!allowRepeat && !drawnItems.includes(result)) {
                    drawnItems.push(result);
                    updateDrawnItemsInfo();
                }
                
                // 添加到歷史記錄
                const now = new Date();
                lotteryHistory.unshift({
                    result: result,
                    time: now.toLocaleString('zh-TW'),
                    listSize: availableList.length,
                    totalSize: currentList.length
                });
                
                // 限制歷史記錄數量
                if (lotteryHistory.length > 50) {
                    lotteryHistory = lotteryHistory.slice(0, 50);
                }
                
                updateHistory();
                updateCurrentList();
                saveToCookies();
                
                // 恢復轉盤點擊
                wheel.style.cursor = 'pointer';
                wheel.style.pointerEvents = 'auto';
                
                isSpinning = false;
                startBtn.disabled = false;
                startBtn.textContent = '🎲 再次抽獎';
            }, 4000);
        }

        // 重置抽獎
        function resetLottery() {
            if (isSpinning) {
                return;
            }
            
            const wheel = document.getElementById('lottery-wheel');
            const wheelText = document.getElementById('wheel-text');
            const resultDisplay = document.getElementById('result-display');
            const startBtn = document.getElementById('start-btn');
            const confettiContainer = document.getElementById('confetti-container');
            
            wheel.style.transform = 'rotate(0deg)';
            wheel.classList.remove('spinning');
            wheel.style.cursor = 'pointer';
            wheel.style.pointerEvents = 'auto';
            wheelText.classList.remove('spinning-text');
            wheelText.textContent = '點擊開始抽獎';
            resultDisplay.textContent = '🎯 抽獎結果將顯示在這裡';
            resultDisplay.classList.remove('winner');
            startBtn.textContent = '🎲 開始抽獎';
            
            // 清除特效
            confettiContainer.innerHTML = '';
        }

        // 音效控制
        function toggleSound() {
            soundEnabled = !soundEnabled;
            const soundBtn = document.getElementById('sound-btn');
            soundBtn.textContent = soundEnabled ? '🔊 音效' : '🔇 靜音';
            saveToCookies();
        }

        // 播放旋轉音效
        function playSpinSound() {
            // 使用Web Audio API創建音效
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.setValueAtTime(220, audioContext.currentTime);
                oscillator.frequency.exponentialRampToValueAtTime(440, audioContext.currentTime + 0.5);
                
                gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
                
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.5);
            } catch (e) {
                console.log('音效播放失敗:', e);
            }
        }

        // 播放勝利音效
        function playWinSound() {
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                // 勝利音效序列
                const notes = [261.63, 329.63, 392.00, 523.25]; // C-E-G-C
                let time = audioContext.currentTime;
                
                notes.forEach((freq, index) => {
                    const osc = audioContext.createOscillator();
                    const gain = audioContext.createGain();
                    
                    osc.connect(gain);
                    gain.connect(audioContext.destination);
                    
                    osc.frequency.setValueAtTime(freq, time);
                    gain.gain.setValueAtTime(0.1, time);
                    gain.gain.exponentialRampToValueAtTime(0.01, time + 0.2);
                    
                    osc.start(time);
                    osc.stop(time + 0.2);
                    
                    time += 0.15;
                });
            } catch (e) {
                console.log('勝利音效播放失敗:', e);
            }
        }

        // 創建彩帶效果
        function createConfetti() {
            const confettiContainer = document.getElementById('confetti-container');
            const colors = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#ffeaa7', '#dda0dd', '#98d8c8'];
            
            for (let i = 0; i < 50; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.animationDelay = Math.random() * 3 + 's';
                confetti.style.animationDuration = (Math.random() * 2 + 2) + 's';
                
                confettiContainer.appendChild(confetti);
                
                // 3秒後移除
                setTimeout(() => {
                    if (confetti.parentNode) {
                        confetti.parentNode.removeChild(confetti);
                    }
                }, 5000);
            }
        }

        // 創建煙花效果
        function createFireworks() {
            const wheelContainer = document.querySelector('.wheel-container');
            const colors = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#ffeaa7', '#dda0dd'];
            
            for (let i = 0; i < 3; i++) {
                setTimeout(() => {
                    const centerX = wheelContainer.offsetWidth / 2;
                    const centerY = wheelContainer.offsetHeight / 2;
                    
                    for (let j = 0; j < 12; j++) {
                        const firework = document.createElement('div');
                        firework.className = 'fireworks';
                        firework.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                        firework.style.left = centerX + 'px';
                        firework.style.top = centerY + 'px';
                        
                        const angle = (j * 30) * Math.PI / 180;
                        const distance = 100 + Math.random() * 50;
                        const dx = Math.cos(angle) * distance;
                        const dy = Math.sin(angle) * distance;
                        
                        firework.style.setProperty('--dx', dx + 'px');
                        firework.style.setProperty('--dy', dy + 'px');
                        
                        wheelContainer.appendChild(firework);
                        
                        setTimeout(() => {
                            if (firework.parentNode) {
                                firework.parentNode.removeChild(firework);
                            }
                        }, 1000);
                    }
                }, i * 200);
            }
        }

        // 更新重複抽獎設定
        function updateRepeatSetting() {
            allowRepeat = document.getElementById('allow-repeat').checked;
            updateDrawnItemsInfo();
            saveToCookies();
        }

        // 更新已抽項目信息
        function updateDrawnItemsInfo() {
            const drawnItemsInfo = document.getElementById('drawn-items-info');
            const drawnCount = document.getElementById('drawn-count');
            const remainingCount = document.getElementById('remaining-count');
            const resetDrawnBtn = document.getElementById('reset-drawn-btn');
            
            if (!allowRepeat && drawnItems.length > 0) {
                drawnItemsInfo.style.display = 'block';
                drawnCount.textContent = drawnItems.length;
                remainingCount.textContent = currentList.length - drawnItems.length;
                resetDrawnBtn.style.display = 'inline-block';
            } else {
                drawnItemsInfo.style.display = 'none';
                resetDrawnBtn.style.display = 'none';
            }
        }

        // 重置已抽項目
        function resetDrawnItems() {
            if (confirm('確定要重置已抽項目嗎？所有項目將重新可以被抽中。')) {
                drawnItems = [];
                updateDrawnItemsInfo();
                updateCurrentList();
                saveToCookies();
                showMessage('已抽項目已重置', 'success');
            }
        }

        // 更新歷史記錄
        function updateHistory() {
            const historyDiv = document.getElementById('history-list');
            if (lotteryHistory.length === 0) {
                historyDiv.innerHTML = '<em>暫無抽獎記錄</em>';
            } else {
                historyDiv.innerHTML = lotteryHistory.map((record, index) => 
                    `<div class="history-item">
                        <span><strong>${record.result}</strong></span>
                        <span style="font-size: 0.9em; color: #6c757d;">
                            ${record.time} (${record.listSize || record.totalSize || 0}項)
                        </span>
                    </div>`
                ).join('');
            }
        }

        // 清除歷史
        function clearHistory() {
            if (confirm('確定要清除所有抽獎歷史嗎？')) {
                lotteryHistory = [];
                updateHistory();
                saveToCookies();
                showMessage('歷史記錄已清除', 'success');
            }
        }

        // 保存到Cookie
        function saveToCookies() {
            const data = {
                currentList: currentList,
                lotteryHistory: lotteryHistory,
                drawnItems: drawnItems,
                allowRepeat: allowRepeat,
                soundEnabled: soundEnabled,
                timestamp: Date.now()
            };
            
            // 設置7天過期
            const expires = new Date();
            expires.setDate(expires.getDate() + 7);
            
            document.cookie = `lottery_data=${encodeURIComponent(JSON.stringify(data))}; expires=${expires.toUTCString()}; path=/`;
        }

        // 從Cookie載入
        function loadFromCookies() {
            const cookies = document.cookie.split(';');
            const lotteryDataCookie = cookies.find(cookie => cookie.trim().startsWith('lottery_data='));
            
            if (lotteryDataCookie) {
                try {
                    const data = JSON.parse(decodeURIComponent(lotteryDataCookie.split('=')[1]));
                    currentList = data.currentList || [];
                    lotteryHistory = data.lotteryHistory || [];
                    drawnItems = data.drawnItems || [];
                    allowRepeat = data.allowRepeat !== undefined ? data.allowRepeat : true;
                    soundEnabled = data.soundEnabled !== undefined ? data.soundEnabled : true;
                    
                    // 更新UI狀態
                    document.getElementById('allow-repeat').checked = allowRepeat;
                    document.getElementById('sound-btn').textContent = soundEnabled ? '🔊 音效' : '🔇 靜音';
                    
                    // 更新各個預覽
                    updateManualPreview();
                    updateRangePreview();
                } catch (e) {
                    console.error('載入Cookie數據失敗:', e);
                }
            }
        }

        // 顯示消息
        function showMessage(message, type = 'success') {
            const messageDiv = document.createElement('div');
            messageDiv.className = type === 'success' ? 'success-message' : 'error-message';
            messageDiv.textContent = message;
            
            // 插入到第一個標籤內容前
            const firstTab = document.querySelector('.tab-content');
            firstTab.parentNode.insertBefore(messageDiv, firstTab);
            
            // 3秒後移除
            setTimeout(() => {
                messageDiv.remove();
            }, 3000);
        }

        // 點擊事件委託
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('tab-button')) {
                document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
                e.target.classList.add('active');
            }
        });
    </script>
</body>
</html>
