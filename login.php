<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <base href="<?= BASE_URL ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập - CRM Pro</title>
    <script>const BASE_URL = "<?= BASE_URL ?>";</script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        body {
            background: linear-gradient(135deg, #0d6efd 0%, #003e9c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .login-card {
            max-width: 440px;
            width: 100%;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            padding: 2.5rem;
        }
        .account-hint-box {
            background-color: #f8f9fa;
            border-left: 4px solid #0d6efd;
            border-radius: 6px;
            padding: 12px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle mb-3" style="width: 60px; height: 60px;">
            <i class="bi bi-person-lines-fill fs-2"></i>
        </div>
        <h3 class="fw-bold mb-1">CRM <span class="text-primary">Pro</span></h3>
        <p class="text-muted small">Hệ thống quản lý khách hàng doanh nghiệp</p>
    </div>

    <div id="loginAlert" class="alert alert-danger d-none py-2 small" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <span id="alertMsg"></span>
    </div>

    <form id="loginForm">
        <div class="mb-3">
            <label for="username" class="form-label small fw-semibold">Tên đăng nhập</label>
            <div class="input-group">
                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-person"></i></span>
                <input type="text" class="form-control border-start-0 ps-0" id="username" name="username" placeholder="Nhập username..." required autofocus autocomplete="username">
            </div>
        </div>

        <div class="mb-4">
            <label for="password" class="form-label small fw-semibold">Mật khẩu</label>
            <div class="input-group">
                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-lock"></i></span>
                <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" placeholder="Nhập password..." required autocomplete="current-password">
                <button class="btn btn-outline-secondary border-start-0" type="button" id="togglePassword">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>

        <button type="submit" id="btnLogin" class="btn btn-primary w-100 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2">
            <span>Đăng Nhập</span>
            <div id="loginSpinner" class="spinner-border spinner-border-sm d-none" role="status"></div>
        </button>
    </form>

    <div class="account-hint-box mt-4">
        <div class="fw-semibold text-primary mb-1"><i class="bi bi-info-circle me-1"></i> Tài khoản mẫu kiểm thử:</div>
        <div class="d-flex justify-content-between text-muted mt-1">
            <span><strong>Admin:</strong> admin</span>
            <span><strong>Pass:</strong> admin123</span>
        </div>
        <div class="d-flex justify-content-between text-muted">
            <span><strong>Staff:</strong> staff_nam</span>
            <span><strong>Pass:</strong> staff123</span>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // Ẩn/Hiện mật khẩu
    $('#togglePassword').on('click', function() {
        const passInput = $('#password');
        const icon = $(this).find('i');
        if (passInput.attr('type') === 'password') {
            passInput.attr('type', 'text');
            icon.removeClass('bi-eye').addClass('bi-eye-slash');
        } else {
            passInput.attr('type', 'password');
            icon.removeClass('bi-eye-slash').addClass('bi-eye');
        }
    });

    // Xử lý gửi Form đăng nhập qua AJAX
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();

        const username = $('#username').val().trim();
        const password = $('#password').val();
        const alertBox = $('#loginAlert');
        const btnLogin = $('#btnLogin');
        const spinner = $('#loginSpinner');

        if (!username || !password) {
            alertBox.removeClass('d-none').find('#alertMsg').text('Vui lòng nhập đầy đủ thông tin!');
            return;
        }

        // Bật loading
        btnLogin.prop('disabled', true);
        spinner.removeClass('d-none');
        alertBox.addClass('d-none');

        $.ajax({
            url: 'api/auth.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'login',
                username: username,
                password: password
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.redirect || 'index.php';
                } else {
                    alertBox.removeClass('d-none').find('#alertMsg').text(response.message);
                    btnLogin.prop('disabled', false);
                    spinner.addClass('d-none');
                }
            },
            error: function(xhr) {
                let msg = 'Đăng nhập thất bại. Vui lòng thử lại!';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                alertBox.removeClass('d-none').find('#alertMsg').text(msg);
                btnLogin.prop('disabled', false);
                spinner.addClass('d-none');
            }
        });
    });
});
</script>
</body>
</html>
