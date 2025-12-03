<?php
// auth.php
require_once 'config.php';

function require_login() {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function current_user_name() {
    return $_SESSION['user_name'] ?? 'Guest';
}

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}
?>
