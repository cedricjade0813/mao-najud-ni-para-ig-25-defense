@echo off
echo ========================================
echo    ComLab Portable Server Setup
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

echo [STEP 1] Stopping Apache if running...
taskkill /F /IM httpd.exe >nul 2>&1

echo [STEP 2] Creating portable virtual host configuration...

REM Create the portable virtual host file
(
echo # Portable ComLab Server Configuration
echo # This works on ANY laptop/PC with XAMPP
echo.
echo # Catch-all virtual host for any IP address
echo ^<VirtualHost *:80^>
echo     DocumentRoot "C:/xampp/htdocs/comlab"
echo     DirectoryIndex index.php index.html index.htm
echo     
echo     ^<Directory "C:/xampp/htdocs/comlab"^>
echo         AllowOverride All
echo         Require all granted
echo         Options Indexes FollowSymLinks
echo     ^</Directory^>
echo     
echo     # Error and access logs
echo     ErrorLog "C:/xampp/apache/logs/comlab_error.log"
echo     CustomLog "C:/xampp/apache/logs/comlab_access.log" common
echo ^</VirtualHost^>
echo.
echo # Localhost virtual host
echo ^<VirtualHost *:80^>
echo     ServerName localhost
echo     DocumentRoot "C:/xampp/htdocs/comlab"
echo     DirectoryIndex index.php index.html index.htm
echo     
echo     ^<Directory "C:/xampp/htdocs/comlab"^>
echo         AllowOverride All
echo         Require all granted
echo         Options Indexes FollowSymLinks
echo     ^</Directory^>
echo ^</VirtualHost^>
) > "C:\xampp\apache\conf\extra\httpd-vhosts-comlab.conf"

echo [STEP 3] Updating main Apache configuration...

REM Backup original httpd.conf
if not exist "C:\xampp\apache\conf\httpd.conf.backup" (
    copy "C:\xampp\apache\conf\httpd.conf" "C:\xampp\apache\conf\httpd.conf.backup" >nul
    echo [INFO] Created backup of original httpd.conf
)

REM Update DocumentRoot in httpd.conf
powershell -Command "(Get-Content 'C:\xampp\apache\conf\httpd.conf') -replace 'DocumentRoot \"C:/xampp/htdocs\"', 'DocumentRoot \"C:/xampp/htdocs/comlab\"' | Set-Content 'C:\xampp\apache\conf\httpd.conf'"
powershell -Command "(Get-Content 'C:\xampp\apache\conf\httpd.conf') -replace '<Directory \"C:/xampp/htdocs\">', '<Directory \"C:/xampp/htdocs/comlab\">' | Set-Content 'C:\xampp\apache\conf\httpd.conf'"

REM Add virtual host include if not already present
findstr /C:"httpd-vhosts-comlab.conf" "C:\xampp\apache\conf\httpd.conf" >nul
if %errorLevel% neq 0 (
    echo Include conf/extra/httpd-vhosts-comlab.conf >> "C:\xampp\apache\conf\httpd.conf"
    echo [INFO] Added virtual host include to httpd.conf
)

echo [STEP 4] Testing Apache configuration...
"C:\xampp\apache\bin\httpd.exe" -t
if %errorLevel% neq 0 (
    echo [ERROR] Apache configuration test failed!
    echo Restoring original configuration...
    copy "C:\xampp\apache\conf\httpd.conf.backup" "C:\xampp\apache\conf\httpd.conf" >nul
    pause
    exit /b 1
)

echo [STEP 5] Starting Apache...
start "" "C:\xampp\apache\bin\httpd.exe"

echo.
echo ========================================
echo    Setup Complete!
echo ========================================
echo.
echo Your ComLab server is now configured and running!
echo.
echo Access your website at:
echo - http://localhost
echo - http://[your-ip-address]
echo.
echo To find your IP address, visit:
echo http://localhost/get_my_ip.php
echo.
echo This setup will work on ANY laptop/PC with XAMPP!
echo.
pause
