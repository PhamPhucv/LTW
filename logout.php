<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    $pdo = getDBConnection();
    log_activity($pdo, $_SESSION['user_id'], 'LOGOUT', 'auth', $_SESSION['user_id']);
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

header('Location: ' . BASE_URL . 'login.php');
exit;
