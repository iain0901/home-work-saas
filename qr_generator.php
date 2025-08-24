<?php
// QR碼生成頁面
if (!isset($_GET['code']) || !isset($_GET['name'])) {
    http_response_code(400);
    exit('Missing parameters');
}

$share_code = $_GET['code'];
$classroom_name = $_GET['name'];
$base_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$share_url = $base_url . '/upload.php?code=' . urlencode($share_code);

// 設定回應標頭
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR碼 - <?php echo htmlspecialchars($classroom_name); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 40px 20px;
            background: #f8f9fa;
            margin: 0;
        }
        
        .qr-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 500px;
            margin: 0 auto;
        }
        
        .qr-title {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.5em;
        }
        
        .qr-code {
            margin: 30px 0;
        }
        
        .qr-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }
        
        .share-url {
            background: #e9ecef;
            padding: 10px;
            border-radius: 4px;
            word-break: break-all;
            font-family: monospace;
            margin: 10px 0;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .btn-download {
            background: #28a745;
        }
        
        .btn-download:hover {
            background: #218838;
        }
        
        .instructions {
            text-align: left;
            margin-top: 30px;
            padding: 20px;
            background: #d1ecf1;
            border-radius: 8px;
            border-left: 4px solid #17a2b8;
        }
        
        .instructions h4 {
            margin-top: 0;
            color: #0c5460;
        }
        
        .instructions ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        
        .instructions li {
            margin: 8px 0;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="qr-container">
        <h1 class="qr-title">🏫 <?php echo htmlspecialchars($classroom_name); ?></h1>
        <h2>📱 作業上傳QR碼</h2>
        
        <div class="qr-code">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?php echo urlencode($share_url); ?>" 
                 alt="QR Code" id="qrImage">
        </div>
        
        <div class="qr-info">
            <strong>📝 教室代碼：</strong><?php echo htmlspecialchars($share_code); ?><br>
            <strong>🔗 上傳連結：</strong>
            <div class="share-url"><?php echo htmlspecialchars($share_url); ?></div>
        </div>
        
        <button onclick="downloadQR()" class="btn btn-download">💾 下載QR碼</button>
        <button onclick="copyLink()" class="btn">📋 複製連結</button>
        <a href="admin_classrooms.php" class="btn">🔙 返回教室管理</a>
        
        <div class="instructions">
            <h4>📋 使用說明</h4>
            <ol>
                <li><strong>分享QR碼：</strong>將此QR碼分享給學生，或列印張貼在教室</li>
                <li><strong>掃描上傳：</strong>學生用手機掃描QR碼即可直接進入上傳頁面</li>
                <li><strong>自動加入：</strong>掃描後會自動加入「<?php echo htmlspecialchars($classroom_name); ?>」教室</li>
                <li><strong>填寫上傳：</strong>學生填寫作業資訊並提交即可</li>
            </ol>
        </div>
    </div>

    <script>
        // 下載QR碼
        function downloadQR() {
            const img = document.getElementById('qrImage');
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            canvas.width = img.width;
            canvas.height = img.height;
            
            img.onload = function() {
                ctx.drawImage(img, 0, 0);
                
                // 創建下載連結
                const link = document.createElement('a');
                link.download = '<?php echo htmlspecialchars($classroom_name); ?>_QR碼.png';
                link.href = canvas.toDataURL();
                link.click();
            };
            
            // 如果圖片已經載入
            if (img.complete) {
                img.onload();
            }
        }

        // 複製連結
        function copyLink() {
            const url = '<?php echo $share_url; ?>';
            const guideText = `🏫 <?php echo htmlspecialchars($classroom_name); ?> - 作業上傳連結

📝 請點擊以下連結上傳您的作業：
${url}

💡 使用說明：
1. 點擊連結會自動加入教室
2. 填寫您的作業資訊
3. 點擊「上傳作業」完成提交

📞 如有問題請聯絡老師`;

            if (navigator.clipboard) {
                navigator.clipboard.writeText(guideText).then(function() {
                    alert('📋 連結和說明已複製到剪貼板！');
                }).catch(function() {
                    fallbackCopy(guideText);
                });
            } else {
                fallbackCopy(guideText);
            }
        }

        // 降級複製方法
        function fallbackCopy(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                alert('📋 連結和說明已複製到剪貼板！');
            } catch (err) {
                alert('複製失敗，請手動複製連結：\n' + '<?php echo $share_url; ?>');
            }
            document.body.removeChild(textarea);
        }
    </script>
</body>
</html>
