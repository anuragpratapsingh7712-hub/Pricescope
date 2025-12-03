<?php
function isLoggedIn() {
    // 1. Check Session
    if (isset($_SESSION['user_id'])) {
        return true;
    }

    // 2. Check Cookie (Serverless Fallback)
    if (isset($_COOKIE['pricescope_user'])) {
        $parts = explode(':', $_COOKIE['pricescope_user']);
        if (count($parts) === 2) {
            $uid = $parts[0];
            $hash = $parts[1];
            $secret = "PriceScope_Secret_Key_99";
            
            if (hash_hmac('sha256', $uid, $secret) === $hash) {
                // Restore Session
                $_SESSION['user_id'] = $uid;
                $_SESSION['user_name'] = "User"; // Placeholder, or fetch from DB if needed
                return true;
            }
        }
    }
    return false;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: index.php");
        exit();
    }
}

function generateCaptcha() {
    $num1 = rand(1, 10);
    $num2 = rand(1, 10);
    $_SESSION['captcha_ans'] = $num1 + $num2;
    return "$num1 + $num2";
}

function verifyCaptcha($ans) {
    if (!isset($_SESSION['captcha_ans'])) return false;
    $isValid = (intval($ans) === $_SESSION['captcha_ans']);
    unset($_SESSION['captcha_ans']); // Prevent replay
    return $isValid;
}

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>
