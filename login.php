<?php
require_once 'config.php';

// Generate simple CAPTCHA
if (empty($_SESSION['login_captcha'])) {
    $_SESSION['login_captcha_a'] = rand(1, 9);
    $_SESSION['login_captcha_b'] = rand(1, 9);
    $_SESSION['login_captcha']   = $_SESSION['login_captcha_a'] + $_SESSION['login_captcha_b'];
}

$login_error = '';

// LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $captcha = $_POST['captcha'] ?? '';

    // HONEYPOT CHECK (Anti-Bot)
    if (!empty($_POST['website'])) {
        die("Spam detected.");
    }

    if ($captcha != $_SESSION['login_captcha']) {
        $login_error = "Incorrect CAPTCHA.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || !$password) {
        $login_error = "Please enter valid email and password.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['is_admin'] = $user['is_admin'] ?? 0;
            
            // COOKIE AUTH (For Serverless/Vercel persistence)
            $secret = "PriceScope_Secret_Key_99"; 
            $hash = hash_hmac('sha256', $user['id'], $secret);
            $cookieValue = $user['id'] . ':' . $hash;
            setcookie('pricescope_user', $cookieValue, time() + (86400 * 30), "/", "", true, true); // 30 days, Secure, HttpOnly
            
            header('Location: dashboard.php');
            exit;
        } else {
            $login_error = "Invalid credentials.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - PriceScope Pro</title>
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-box glass-card">
            <h2 style="text-align: center; margin-top: 0;">TERMINAL <span style="color: var(--neon-cyan);">ACCESS</span></h2>
            
            <?php if ($login_error): ?>
                <p style="color: var(--neon-red); text-align: center;"><?= htmlspecialchars($login_error) ?></p>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="login">
                <!-- Honeypot -->
                <input type="text" name="website" style="display:none;">

                <div class="input-group" style="margin-bottom: 20px;">
                    <label>ID / EMAIL</label>
                    <input type="text" name="email" placeholder="user@pricescope.pro" required>
                </div>
                <div class="input-group" style="margin-bottom: 20px;">
                    <label>PASSWORD</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <div class="input-group" style="display: flex; gap: 10px; align-items: center; margin-bottom: 20px;">
                    <input type="text" name="captcha" placeholder="<?= $_SESSION['login_captcha_a'] ?> + <?= $_SESSION['login_captcha_b'] ?> = ?" style="width: 100px;" required>
                    <span style="font-size: 0.8em; color: #94a3b8;">Human Check</span>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">INITIALIZE SESSION</button>
            </form>
            
            <div class="login-mascot" style="font-size: 80px;">🐧</div>
        </div>
    </div>
</body>
</html>
