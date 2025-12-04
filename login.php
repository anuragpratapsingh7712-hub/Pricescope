<?php
require_once 'config.php';

$login_error = '';
$secret = "PriceScope_Secret_Key_99";

// LOGIN LOGIC
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $captcha = trim($_POST['captcha'] ?? '');

    // HONEYPOT
    if (!empty($_POST['website'])) {
        die("Spam detected.");
    }

    // CAPTCHA VERIFICATION
    $inputHash = hash_hmac('sha256', $captcha, $secret);
    $cookieHash = $_COOKIE['captcha_hash'] ?? '';

    if (empty($cookieHash) || !hash_equals($cookieHash, $inputHash)) {
        $login_error = "Incorrect Security Answer.";
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
            
            // COOKIE AUTH
            $authHash = hash_hmac('sha256', $user['id'], $secret);
            $cookieValue = $user['id'] . ':' . $authHash;
            setcookie('pricescope_user', $cookieValue, time() + (86400 * 30), "/", "", true, true);
            
            // Clear captcha
            setcookie('captcha_hash', '', time() - 3600, '/');

            header('Location: dashboard.php');
            exit;
        } else {
            $login_error = "Invalid credentials.";
        }
    }
}

// GENERATE NEW MATH CAPTCHA (Runs on every page load/render)
$n1 = rand(1, 9);
$n2 = rand(1, 9);
$ans = $n1 + $n2;
$newHash = hash_hmac('sha256', (string)$ans, $secret);
$isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
setcookie('captcha_hash', $newHash, time() + 300, '/', '', $isSecure, true);

?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | PriceScope</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Outfit', 'sans-serif'] },
                    colors: { deep: '#020617' }
                }
            }
        }
    </script>
    <style>
        body { 
            background-color: #020617; 
            color: white; 
            background-image: 
                radial-gradient(at 0% 0%, rgba(168, 85, 247, 0.2) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(6, 182, 212, 0.2) 0px, transparent 50%);
        }
        .neon-border {
            position: relative;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        .neon-border:focus-within {
            border-color: #06b6d4;
            box-shadow: 0 0 20px rgba(6, 182, 212, 0.2);
        }
    </style>
</head>
<body class="h-screen flex items-center justify-center p-4 relative overflow-hidden">
    
    <!-- Floating particles -->
    <div class="absolute top-10 left-10 w-2 h-2 bg-cyan-400 rounded-full animate-ping"></div>
    <div class="absolute bottom-10 right-10 w-2 h-2 bg-purple-400 rounded-full animate-ping delay-700"></div>

    <div class="w-full max-w-md">
        <!-- Mascot Greeting -->
        <div class="text-center mb-8 relative">
            <div class="w-24 h-24 mx-auto bg-gradient-to-b from-cyan-500 to-blue-600 rounded-full p-1 mb-4 shadow-[0_0_40px_rgba(6,182,212,0.4)]">
                <img src="mascot.jpg" class="w-full h-full rounded-full object-cover bg-black" alt="Blu">
            </div>
            <h2 class="text-3xl font-bold">Welcome Back!</h2>
            <p class="text-slate-400">Ready to save some money?</p>
        </div>

        <?php if ($login_error): ?>
            <div class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/50 text-red-400 text-center text-sm font-medium">
                <?= htmlspecialchars($login_error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4 neon-border p-8 rounded-3xl shadow-2xl">
            <input type="hidden" name="action" value="login">
            <input type="text" name="website" style="display:none;"> <!-- Honeypot -->

            <div>
                <label class="block text-xs font-bold text-cyan-400 uppercase tracking-wider mb-2">Email</label>
                <input type="email" name="email" class="w-full bg-slate-900/50 border border-slate-700 rounded-xl p-4 outline-none focus:border-cyan-400 transition-colors text-white placeholder-slate-600" placeholder="shopper@gmail.com" required>
            </div>
            
            <div>
                <label class="block text-xs font-bold text-cyan-400 uppercase tracking-wider mb-2">Password</label>
                <input type="password" name="password" class="w-full bg-slate-900/50 border border-slate-700 rounded-xl p-4 outline-none focus:border-cyan-400 transition-colors text-white placeholder-slate-600" placeholder="••••••••" required>
            </div>

            <!-- Simple Math Captcha -->
            <div class="bg-slate-800/50 rounded-xl p-3 border border-white/5">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-cyan-400 uppercase tracking-wider">Security Check</span>
                    <img src="mascot.jpg" class="w-5 h-5 rounded-full opacity-50 grayscale" title="Blu is watching!">
                </div>
                <div class="flex gap-2 items-center">
                    <div class="h-12 flex-1 rounded-lg border border-slate-700 bg-slate-900 flex items-center justify-center text-xl font-bold font-mono text-cyan-400 tracking-widest select-none">
                        <?= $n1 ?> + <?= $n2 ?> = ?
                    </div>
                    <input type="number" name="captcha" placeholder="ANSWER" class="w-24 bg-slate-900/50 border border-slate-700 rounded-lg p-3 outline-none focus:border-cyan-400 text-white text-center font-bold placeholder-slate-600" required>
                </div>
            </div>

            <button type="submit" class="w-full py-4 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 font-bold text-white shadow-[0_0_20px_rgba(6,182,212,0.4)] hover:shadow-[0_0_40px_rgba(6,182,212,0.6)] hover:scale-[1.02] transition-all">
                Enter Dashboard
            </button>
        </form>
        
        <p class="text-center mt-6 text-slate-500 text-sm">New to PriceScope? <a href="register.php" class="text-cyan-400 hover:text-cyan-300 font-bold">Create Account</a></p>
    </div>

</body>
</html>
