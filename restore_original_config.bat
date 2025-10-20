@echo off
echo ========================================
echo    Restore Original XAMPP Configuration
echo ========================================
echo.

REM Check if running as administrator
net session >nul 2>&1
if %errorLevel% == 0 (
    echo [INFO] Running with administrator privileges
) else (
    echo [ERROR] Please run this script as Administrator!
    echo Right-click and select "Run as administrator"
    pause
    exit /b 1
)

echo [STEP 1] Stopping Apache...
taskkill /F /IM httpd.exe >nul 2>&1

echo [STEP 2] Restoring original Apache configuration...

REM Check if backup exists
if exist "C:\xampp\apache\conf\httpd.conf.backup" (
    copy "C:\xampp\apache\conf\httpd.conf.backup" "C:\xampp\apache\conf\httpd.conf" >nul
    echo [INFO] Restored original httpd.conf from backup
) else (
    echo [WARNING] No backup found. Manually restoring DocumentRoot...
    powershell -Command "(Get-Content 'C:\xampp\apache\conf\httpd.conf') -replace 'DocumentRoot \"C:/xampp/htdocs/comlab\"', 'DocumentRoot \"C:/xampp/htdocs\"' | Set-Content 'C:\xampp\apache\conf\httpd.conf'"
    powershell -Command "(Get-Content 'C:\xampp\apache\conf\httpd.conf') -replace '<Directory \"C:/xampp/htdocs/comlab\">', '<Directory \"C:/xampp/htdocs\">' | Set-Content 'C:\xampp\apache\conf\httpd.conf'"
)

echo [STEP 3] Removing ComLab virtual host configuration...
if exist "C:\xampp\apache\conf\extra\httpd-vhosts-comlab.conf" (
    del "C:\xampp\apache\conf\extra\httpd-vhosts-comlab.conf"
    echo [INFO] Removed ComLab virtual host configuration
)

echo [STEP 4] Testing Apache configuration...
"C:\xampp\apache\bin\httpd.exe" -t
if %errorLevel% neq 0 (
    echo [ERROR] Apache configuration test failed!
    echo Please check your XAMPP installation.
    pause
    exit /b 1
)

echo [STEP 5] Starting Apache with original configuration...
start "" "C:\xampp\apache\bin\httpd.exe"

echo.
echo ========================================
echo    Restore Complete!
echo ========================================
echo.
echo XAMPP has been restored to its original configuration.
echo.
echo Your ComLab project is still available at:
echo http://localhost/comlab/
echo.
echo To set up the portable server again, run:
echo setup_portable_server.bat
echo.
pause
