@echo off
chcp 65001 >nul
title CRM PRO - He Thong Quan Ly Khach Hang

echo =====================================================================
echo                 KHOI DONG DU AN CRM PRO
echo =====================================================================
echo.

set PHP_CMD=

where php >nul 2>&1
if %ERRORLEVEL% equ 0 (
    set PHP_CMD=php
    goto :FOUND_PHP
)

if exist "C:\xampp\php\php.exe" (
    set PHP_CMD=C:\xampp\php\php.exe
    goto :FOUND_PHP
)

if exist "D:\xampp\php\php.exe" (
    set PHP_CMD=D:\xampp\php\php.exe
    goto :FOUND_PHP
)

if exist "E:\xampp\php\php.exe" (
    set PHP_CMD=E:\xampp\php\php.exe
    goto :FOUND_PHP
)

:NOT_FOUND_PHP
echo [LOI] Khong tim thay trinh thuc thi PHP tren may tinh!
echo Vui long cai dat XAMPP hoac them PHP vao bien moi truong PATH.
echo.
pause
exit /b 1

:FOUND_PHP
echo [OK] Tim thay PHP: %PHP_CMD%
echo.

echo [*] Kiem tra ket noi MySQL va co so du lieu...
"%PHP_CMD%" "%~dp0auto_setup.php"
echo.

echo =====================================================================
echo   He thong dang chay tai: http://localhost:8000/login.php
echo.
echo   Tai khoan dang nhap mau:
echo     - Quan tri vien (Admin): admin     /  admin123
echo     - Nhan vien 1   (Staff): staff_nam /  staff123
echo     - Nhan vien 2   (Staff): staff_hoa /  staff123
echo.
echo   Luu y: Giu cua so nay mo trong suot qua trinh su dung.
echo          Nhan Ctrl + C de tat may chu.
echo =====================================================================
echo.

timeout /t 2 >nul
start http://localhost:8000/login.php

"%PHP_CMD%" -S localhost:8000 -t "%~dp0."
