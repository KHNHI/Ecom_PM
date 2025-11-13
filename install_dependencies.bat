@echo off
echo ========================================
echo  CAI DAT PHPMAILER CHO ECOM WEBSITE
echo ========================================
echo.

REM Check if Composer exists
where composer >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Composer chua duoc cai dat!
    echo.
    echo Vui long cai dat Composer tu: https://getcomposer.org/download/
    echo.
    pause
    exit /b 1
)

echo [OK] Da tim thay Composer
echo.

REM Run composer install
echo Dang cai dat PHPMailer...
echo.
composer install

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo  CAI DAT THANH CONG!
    echo ========================================
    echo.
    echo Thu muc vendor/ da duoc tao
    echo PHPMailer da duoc cai dat
    echo.
    echo Ban co the reload lai trang admin orders bay gio!
    echo.
) else (
    echo.
    echo ========================================
    echo  CAI DAT THAT BAI!
    echo ========================================
    echo.
    echo Vui long chay lenh thu cong:
    echo   composer install
    echo.
)

pause


