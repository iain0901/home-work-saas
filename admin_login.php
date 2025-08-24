<?php
session_start();

// 載入配置助手
require_once 'config_helper.php';
$config = get_config();

$error_message = '';

// 處理登入
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username === $config['admin_username'] && $password === $config['admin_password']) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_login_time'] = time();
        header('Location: admin.php');
        exit;
    } else {
        $error_message = '帳號或密碼錯誤';
    }
}

// 如果已經登入，直接跳轉
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    header('Location: admin.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理員登入 - <?php echo htmlspecialchars($config['school_name'] ?? '學校'); ?></title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <header>
            <h1><?php echo htmlspecialchars($config['school_name'] ?? '學校'); ?></h1>
            <h2>管理員登入</h2>
        </header>

        <section class="admin-login-form">
            <div class="login-container">
                <div class="login-icon">🔐</div>
                <h3>管理員登入</h3>
                
                <?php if ($error_message): ?>
                    <div class="alert error"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="form-group">
                        <label for="username">帳號：</label>
                        <input type="text" id="username" name="username" required placeholder="請輸入管理員帳號">
                    </div>

                    <div class="form-group">
                        <label for="password">密碼：</label>
                        <input type="password" id="password" name="password" required placeholder="請輸入密碼">
                    </div>

                    <button type="submit" class="btn-submit">登入</button>
                </form>

                <div class="login-footer">
                    <a href="index.php" class="back-link">← 返回首頁</a>
                </div>
            </div>
        </section>
    </div>

    <script src="script.js"></script>
</body>
</html>
