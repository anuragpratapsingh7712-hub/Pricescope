<?php
require_once 'config.php';

// Generate simple CAPTCHA
if (empty($_SESSION['login_captcha'])) {
    $_SESSION['login_captcha_a'] = rand(1, 9);
    $_SESSION['login_captcha_b'] = rand(1, 9);
    $_SESSION['login_captcha']   = $_SESSION['login_captcha_a'] + $_SESSION['login_captcha_b'];
}

$login_error = '';
$register_error = '';
$register_success = '';

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
            $_SESSION['is_admin'] = $user['is_admin'] ?? 0; // Fix: Set Admin Flag
            session_write_close(); // Ensure session is saved before redirect
            header('Location: dashboard.php');
            exit;
        } else {
            $login_error = "Invalid credentials.";
        }
    }

    // regenerate CAPTCHA
    unset($_SESSION['login_captcha']);
}

// REGISTER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['reg_email'] ?? '');
    $password = $_POST['reg_password'] ?? '';
    $password2 = $_POST['reg_password2'] ?? '';
    $captcha = $_POST['reg_captcha'] ?? '';

    // HONEYPOT CHECK
    if (!empty($_POST['website'])) {
        die("Spam detected.");
    }

    if ($captcha != $_SESSION['login_captcha']) {
        $register_error = "Incorrect CAPTCHA.";
    } elseif (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $register_error = "Please enter a valid name and email.";
    } elseif (strlen($password) < 8 || !preg_match("/[A-Za-z]/", $password) || !preg_match("/[0-9]/", $password)) {
        $register_error = "Password must be 8+ chars with letters and numbers.";
    } elseif ($password !== $password2) {
        $register_error = "Passwords do not match.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $register_error = "An account with this email already exists.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?,?,?)");
            $stmt->execute([$name, $email, $hash]);
            $register_success = "Account created! Please login.";
        }
    }

    unset($_SESSION['login_captcha']);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PriceScope Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        /* Login Specific Overrides */
        body.auth-body {
            background-color: #0f172a;
            background-image: radial-gradient(circle at 50% 0%, #1e293b 0%, #0f172a 70%);
            color: #fff;
        }
        .auth-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .nav-pills .nav-link {
            color: #94a3b8;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
        }
        .nav-pills .nav-link.active {
            background-color: #3b82f6;
            color: white;
        }
        .form-control-auth {
            background-color: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff !important;
        }
        .form-control-auth::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        .form-control-auth:focus {
            background-color: rgba(15, 23, 42, 0.8);
            border-color: #3b82f6;
            color: #ffffff !important;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }
        .form-label {
            color: #e2e8f0; /* Light gray for better visibility */
        }
    </style>
</head>
<body class="auth-body d-flex align-items-center min-vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="text-center mb-5">
                    <a href="index.php" class="text-decoration-none">
                        <h2 class="fw-bold tracking-tight text-white mb-2">PRICESCOPE <span class="text-primary">PRO</span></h2>
                    </a>
                    <p class="text-muted small text-uppercase letter-spacing-2">Terminal Access</p>
                </div>

                <div class="card auth-card shadow-lg border-0 rounded-4">
                    <div class="card-body p-4">
                        <!-- Toggle Tabs -->
                        <ul class="nav nav-pills nav-fill mb-4 bg-dark-surface p-1 rounded-3 border border-dark-subtle" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active w-100" id="tab-login" onclick="showLogin()" type="button">Login</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link w-100" id="tab-register" onclick="showRegister()" type="button">Register</button>
                            </li>
                        </ul>

                        <!-- Login Form -->
                        <form id="form-login" method="POST">
                            <input type="hidden" name="action" value="login">
                            
                            <?php if ($login_error): ?>
                                <div class="alert alert-danger py-2 small border-0 bg-danger bg-opacity-10 text-danger mb-3">
                                    <?= htmlspecialchars($login_error) ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($register_success): ?>
                                <div class="alert alert-success py-2 small border-0 bg-success bg-opacity-10 text-success mb-3">
                                    <?= htmlspecialchars($register_success) ?>
                                </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label small text-uppercase fw-bold">Email Address</label>
                                <input type="email" name="email" class="form-control form-control-auth" placeholder="name@example.com" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-uppercase fw-bold">Password</label>
                                <input type="password" name="password" class="form-control form-control-auth" placeholder="••••••••" required>
                            </div>
                            
                            <!-- Honeypot -->
                            <input type="text" name="website" style="display:none">

                            <div class="mb-3">
                                <label class="form-label small text-uppercase fw-bold">Captcha: <?= $_SESSION['login_captcha_a'] ?> + <?= $_SESSION['login_captcha_b'] ?> = ?</label>
                                <input type="number" name="captcha" class="form-control form-control-auth" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold rounded-3 mt-2">Authenticate</button>
                        </form>

                        <!-- Register Form (Hidden by default) -->
                        <form id="form-register" method="POST" style="display: none;">
                            <input type="hidden" name="action" value="register">
                            
                            <?php if ($register_error): ?>
                                <div class="alert alert-danger py-2 small border-0 bg-danger bg-opacity-10 text-danger mb-3">
                                    <?= htmlspecialchars($register_error) ?>
                                </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label small text-uppercase fw-bold">Full Name</label>
                                <input type="text" name="name" class="form-control form-control-auth" placeholder="John Doe" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-uppercase fw-bold">Email Address</label>
                                <input type="email" name="reg_email" class="form-control form-control-auth" placeholder="name@example.com" required>
                            </div>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label small text-uppercase fw-bold">Password</label>
                                    <input type="password" name="reg_password" class="form-control form-control-auth" required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label small text-uppercase fw-bold">Confirm</label>
                                    <input type="password" name="reg_password2" class="form-control form-control-auth" required>
                                </div>
                            </div>

                            <!-- Honeypot -->
                            <input type="text" name="website" style="display:none">

                            <div class="mb-3">
                                <label class="form-label small text-uppercase fw-bold">Captcha: <?= $_SESSION['login_captcha_a'] ?> + <?= $_SESSION['login_captcha_b'] ?> = ?</label>
                                <input type="number" name="reg_captcha" class="form-control form-control-auth" required>
                            </div>

                            <button type="submit" class="btn btn-success w-100 py-2 fw-bold rounded-3 mt-2">Create Account</button>
                        </form>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <a href="index.php" class="text-muted small text-decoration-none hover-white transition-all">← Return to Terminal</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showLogin() {
            document.getElementById('form-login').style.display = 'block';
            document.getElementById('form-register').style.display = 'none';
            document.getElementById('tab-login').classList.add('active');
            document.getElementById('tab-register').classList.remove('active');
        }

        function showRegister() {
            document.getElementById('form-login').style.display = 'none';
            document.getElementById('form-register').style.display = 'block';
            document.getElementById('tab-login').classList.remove('active');
            document.getElementById('tab-register').classList.add('active');
        }

        // Auto-switch if there's a register error
        <?php if ($register_error): ?>
            showRegister();
        <?php endif; ?>
    </script>
</body>
</html>
