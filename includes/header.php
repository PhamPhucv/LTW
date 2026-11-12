<?php
/**
 * includes/header.php
 * Header dùng chung cho Dashboard và Quản lý khách hàng
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login_page();
$currentUser = current_user();
$csrfToken = generate_csrf_token();
$activePage = $currentPage ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <base href="<?= BASE_URL ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($csrfToken) ?>">
    <meta name="user-role" content="<?= e($currentUser['role'] ?? 'staff') ?>">
    <meta name="user-id" content="<?= e($currentUser['id'] ?? '') ?>">
    <title><?= e($pageTitle ?? 'Quản Lý Khách Hàng') ?> - CRM Mini</title>
    <script>const BASE_URL = "<?= BASE_URL ?>";</script>

    <!-- Bootstrap 5.3 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">

<!-- Navbar chính -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm py-2">
    <div class="container-fluid px-lg-4">
        <a class="navbar-brand d-flex align-items-center fw-bold text-primary-emphasis" href="index.php">
            <i class="bi bi-person-lines-fill text-primary fs-3 me-2"></i>
            <span class="text-white">CRM<span class="text-primary">Pro</span></span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 fw-medium">
                <li class="nav-item">
                    <a class="nav-link <?= $activePage === 'dashboard' ? 'active text-primary fw-semibold' : '' ?>" href="index.php">
                        <i class="bi bi-speedometer2 me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activePage === 'customers' ? 'active text-primary fw-semibold' : '' ?>" href="customers.php">
                        <i class="bi bi-people-fill me-1"></i> Quản lý khách hàng
                    </a>
                </li>
            </ul>

            <!-- Thông tin người dùng đăng nhập -->
            <div class="d-flex align-items-center gap-3">
                <div class="d-none d-md-flex flex-column text-end">
                    <span class="text-white small fw-semibold"><?= e($currentUser['full_name']) ?></span>
                    <span class="badge <?= $currentUser['role'] === 'admin' ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-info-subtle text-info border border-info-subtle' ?> align-self-end mt-1 text-uppercase" style="font-size: 0.7rem;">
                        <i class="bi <?= $currentUser['role'] === 'admin' ? 'bi-shield-check' : 'bi-person-badge' ?> me-1"></i><?= e($currentUser['role']) ?>
                    </span>
                </div>

                <div class="dropdown">
                    <button class="btn btn-outline-light rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle fs-5"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li class="px-3 py-2 border-bottom d-md-none">
                            <div class="fw-bold"><?= e($currentUser['full_name']) ?></div>
                            <small class="text-muted">@<?= e($currentUser['username']) ?> (<?= e($currentUser['role']) ?>)</small>
                        </li>
                        <li>
                            <a class="dropdown-item text-danger d-flex align-items-center" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i> Đăng xuất
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Container thông báo Toast góc trên bên phải -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100;">
    <div id="liveToast" class="toast align-items-center border-0 shadow-lg text-white" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center fs-6">
                <i id="toastIcon" class="bi me-2 fs-5"></i>
                <span id="toastMessage">Thông báo</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
