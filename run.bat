@echo off
cd /d "%~dp0"
chcp 65001 >nul
title CRM PRO - He Thong Quan Ly Khach Hang

echo =====================================================================
echo                 KHOI DONG DU AN CRM PRO
echo =====================================================================
echo.

if not exist "login.php" (
    echo [LOI QUAN TRONG] Khong tim thay file login.php!
    echo.
    echo Nguyen nhan pho bien:
    echo   Ban dang mo truc tiep file run.bat tu trong file .ZIP ma chua Giai nen.
    echo.
    echo Cach xu ly:
    echo   1. Dong cua so nay.
    echo   2. Chuot phai vao file .zip tai ve, chon "Extract All..." (Giai nen).
    echo   3. Vao thu muc sau khi giai nen va chay lai run.bat.
    echo =====================================================================
    echo.
    pause
    exit /b 1
)

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
echo Vui long cai dat XAMPP hoac them thu muc PHP vao bien moi truong PATH.
echo.
pause
exit /b 1

:FOUND_PHP
echo [OK] Su dung PHP tai: %PHP_CMD%
echo.

echo [*] Dang kiem tra ket noi MySQL va co so du lieu...
"%PHP_CMD%" auto_setup.php
if %ERRORLEVEL% neq 0 (
    echo.
    echo [LOI] Khong the ket noi MySQL. Vui long bat MySQL trong XAMPP roi chay lai!
    echo.
    pause
    exit /b 1
)

set PORT=8000
if exist ".port" (
    set /p PORT=<.port
    del .port >nul 2>&1
)

echo.
echo =====================================================================
echo   He thong dang khoi chay tai: http://localhost:%PORT%/login.php
echo.
echo   Tai khoan dang nhap mau:
echo     - Quan tri vien (Admin): admin     /  admin123
echo     - Nhan vien 1   (Staff): staff_nam /  staff123
echo     - Nhan vien 2   (Staff): staff_hoa /  staff123
echo.
echo   Luu y: Giu cua so nay mo trong suot qua trinh su dung.
echo          Nhan Ctrl + C de dung may chu.
echo =====================================================================
echo.

timeout /t 2 >nul
start http://localhost:%PORT%/login.php

"%PHP_CMD%" -S localhost:%PORT%
