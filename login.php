<?php
session_start();
require_once 'db.php'; // Gọi file kết nối CSDL ở trên cùng

$error_message = '';

// Xử lý khi người dùng bấm nút Đăng nhập (Gửi form qua phương thức POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        // 1. Tìm user trong cơ sở dữ liệu
        $stmt = $pdo->prepare('SELECT id, full_name, password, role FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // 2. So khớp mật khẩu băm (password_verify)
        if ($user && password_verify($password, $user['password'])) {
            // 3. Đăng nhập thành công -> Lưu thông tin vào Session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role']; // 'staff' hoặc 'appraiser'
            
            // 4. Chuyển hướng sang trang Dashboard
            header('Location: dashboard.php');
            exit;
        } else {
            // Đăng nhập thất bại
            $error_message = 'Tên hoặc mật khẩu đăng nhập không chính xác';
        }
    } else {
        $error_message = 'Vui lòng nhập đầy đủ thông tin';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Hệ thống Back-Office nội bộ</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #f3f6f9; 
            --shadow-light: #ffffff;
            --shadow-dark: #d1d9e6; 
            --text-main: #1e293b;
            --text-muted: #64748b;
            --primary: #38bdf8;
            --primary-dark: #0284c7;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, sans-serif;
        }

        body {
            background-color: var(--bg-color);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: var(--text-main);
        }

        .neo-card {
            width: 380px;
            padding: 48px 40px;
            border-radius: 24px;
            background: var(--bg-color);
            box-shadow: 5px 5px 12px var(--shadow-dark), 
                       -5px -5px 12px var(--shadow-light);
            text-align: center;
        }

        .logo-wrapper {
            width: 64px; 
            height: 64px;
            margin: 0 auto 24px;
            border-radius: 50%;
            background: #0f172a;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #38bdf8;
            font-size: 28px;
            box-shadow: 2px 2px 6px var(--shadow-dark), 
                       -2px -2px 6px var(--shadow-light);
        }

        h1 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 6px;
            color: var(--text-main);
            letter-spacing: -0.02em;
        }

        p.subtitle {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 24px;
            font-weight: 500;
        }

        .error-message {
            background-color: #fef2f2;
            color: #ef4444;
            border: 1px solid #f87171;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 20px;
            text-align: center;
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group i.bi-person,
        .input-group i.bi-lock {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 16px;
            z-index: 2;
        }

        .neo-input {
            width: 100%;
            height: 50px;
            border: none;
            outline: none;
            background: var(--bg-color);
            border-radius: 12px;
            padding: 0 46px; 
            font-size: 14px;
            font-weight: 500;
            color: var(--text-main);
            box-shadow: inset 2px 2px 4px var(--shadow-dark), 
                        inset -2px -2px 4px var(--shadow-light);
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            z-index: 2;
            display: flex;
            align-items: center;
        }

        .auth-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            margin-top: -8px;
            padding: 0 4px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .custom-checkbox {
            width: 18px;
            height: 18px;
            border-radius: 6px;
            background: var(--bg-color);
            box-shadow: inset 1.5px 1.5px 3px var(--shadow-dark), 
                        inset -1.5px -1.5px 3px var(--shadow-light);
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 8px;
        }

        .custom-checkbox i {
            font-size: 12px;
            color: var(--primary-dark);
            opacity: 0;
            transform: scale(0.5);
        }

        .remember-me input {
            display: none;
        }

        .remember-me input:checked + .custom-checkbox i {
            opacity: 1;
            transform: scale(1);
        }

        .remember-me span {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted);
        }

        .neo-button {
            width: 100%;
            height: 50px;
            border: none;
            outline: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #38bdf8, #0284c7);
            color: #ffffff;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 24px;
            box-shadow: 3px 3px 8px rgba(56, 189, 248, 0.2), 
                       -3px -3px 8px var(--shadow-light);
        }

        .neo-button:active {
            transform: translateY(2px);
            box-shadow: inset 1.5px 1.5px 3px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>

    <div class="neo-card">
        <div class="logo-wrapper">
            <i class="bi bi-layers-half"></i>
        </div>
        
        <h1>Hệ thống Back-Office</h1>
        <p class="subtitle">Đăng nhập để tiếp tục</p>

        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="input-group">
                <input type="text" class="neo-input" name="username" placeholder="Tên đăng nhập" required autocomplete="username">
                <i class="bi bi-person"></i>
            </div>
            
            <div class="input-group">
                <input type="password" class="neo-input" name="password" id="passwordInput" placeholder="Mật khẩu" required autocomplete="current-password">
                <i class="bi bi-lock"></i>
                
                <button type="button" class="password-toggle" id="togglePassword">
                    <i class="bi bi-eye-slash" id="eyeIcon"></i>
                </button>
            </div>
            
            <div class="auth-options">
                <label class="remember-me">
                    <input type="checkbox">
                    <div class="custom-checkbox">
                        <i class="bi bi-check-lg"></i>
                    </div>
                    <span>Ghi nhớ đăng nhập</span>
                </label>
            </div>

            <button type="submit" class="neo-button">Đăng nhập</button>
        </form>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const passwordInput = document.querySelector('#passwordInput');
        const eyeIcon = document.querySelector('#eyeIcon');

        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            eyeIcon.classList.toggle('bi-eye');
            eyeIcon.classList.toggle('bi-eye-slash');
        });
    </script>
</body>
</html>