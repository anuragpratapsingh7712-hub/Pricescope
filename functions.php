<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
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
