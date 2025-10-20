@echo off
echo ========================================
echo    ComLab Auto-Setup for New Laptop
echo ========================================
echo.
echo This script will automatically set up ComLab server on ANY laptop!
echo.

REM Check if running as administrator
net session >nul 2>&1
if %errorLevel% == 0 (
    echo [INFO] Running with administrator privileges ✓
) else (
    echo [ERROR] Please run this script as Administrator!
    echo Right-click and select "Run as administrator"
    pause
    exit /b 1
)

echo [STEP 1] Checking XAMPP installation...
if not exist "C:\xampp\apache\bin\httpd.exe" (
    echo [ERROR] XAMPP not found! Please install XAMPP first.
    echo Download from: https://www.apachefriends.org/
    pause
    exit /b 1
)
echo [INFO] XAMPP found ✓

echo [STEP 2] Stopping any running Apache processes...
taskkill /F /IM httpd.exe >nul 2>&1
taskkill /F /IM mysqld.exe >nul 2>&1

echo [STEP 3] Creating ComLab directory structure...
if not exist "C:\xampp\htdocs\comlab" mkdir "C:\xampp\htdocs\comlab"
echo [INFO] Directory structure created ✓

echo [STEP 4] Copying ComLab files...
xcopy "%~dp0*" "C:\xampp\htdocs\comlab\" /E /I /Y /EXCLUDE:exclude_list.txt
echo [INFO] Files copied ✓

echo [STEP 5] Configuring Apache for network access...

REM Create the school lab virtual host file
(
echo # ComLab School Lab Server Configuration
echo # Auto-generated setup for network access
echo.
echo # Main server virtual host - accessible from any computer
echo ^<VirtualHost *:80^>
echo     DocumentRoot "C:/xampp/htdocs/comlab"
echo     DirectoryIndex index.php index.html index.htm
echo     
echo     # Allow access from any IP in the school network
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

echo [STEP 6] Updating main Apache configuration...

REM Backup original httpd.conf
if not exist "C:\xampp\apache\conf\httpd.conf.backup" (
    copy "C:\xampp\apache\conf\httpd.conf" "C:\xampp\apache\conf\httpd.conf.backup" >nul
    echo [INFO] Created backup of original httpd.conf ✓
)

REM Update DocumentRoot in httpd.conf
powershell -Command "(Get-Content 'C:\xampp\apache\conf\httpd.conf') -replace 'DocumentRoot \"C:/xampp/htdocs\"', 'DocumentRoot \"C:/xampp/htdocs/comlab\"' | Set-Content 'C:\xampp\apache\conf\httpd.conf'"
powershell -Command "(Get-Content 'C:\xampp\apache\conf\httpd.conf') -replace '<Directory \"C:/xampp/htdocs\">', '<Directory \"C:/xampp/htdocs/comlab\">' | Set-Content 'C:\xampp\apache\conf\httpd.conf'"

REM Add virtual host include if not already present
findstr /C:"httpd-vhosts-comlab.conf" "C:\xampp\apache\conf\httpd.conf" >nul
if %errorLevel% neq 0 (
    echo Include conf/extra/httpd-vhosts-comlab.conf >> "C:\xampp\apache\conf\httpd.conf"
    echo [INFO] Added virtual host include ✓
)

echo [STEP 7] Configuring Windows Firewall...
netsh advfirewall firewall add rule name="ComLab Server HTTP" dir=in action=allow protocol=TCP localport=80 >nul 2>&1
netsh advfirewall firewall add rule name="ComLab Server HTTP Out" dir=out action=allow protocol=TCP localport=80 >nul 2>&1
echo [INFO] Firewall configured ✓

echo [STEP 8] Testing Apache configuration...
"C:\xampp\apache\bin\httpd.exe" -t
if %errorLevel% neq 0 (
    echo [ERROR] Apache configuration test failed!
    echo Restoring original configuration...
    copy "C:\xampp\apache\conf\httpd.conf.backup" "C:\xampp\apache\conf\httpd.conf" >nul
    pause
    exit /b 1
)
echo [INFO] Apache configuration test passed ✓

echo [STEP 9] Starting Apache server...
start "" "C:\xampp\apache\bin\httpd.exe"
timeout /t 3 /nobreak >nul

echo [STEP 10] Getting network information...
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4 Address"') do (
    set "SERVER_IP=%%a"
    set "SERVER_IP=!SERVER_IP: =!"
    goto :ipfound
)
:ipfound

echo.
echo ========================================
echo    🎉 AUTO-SETUP COMPLETE!
echo ========================================
echo.
echo ✅ ComLab server is now running on this laptop!
echo.
echo 📍 Server Information:
echo    IP Address: %SERVER_IP%
echo    Port: 80
echo.
echo 🌐 Access URLs:
echo    From this computer: http://localhost
echo    From other computers: http://%SERVER_IP%
echo.
echo 👥 For other students to connect:
echo    1. Open their web browser
echo    2. Go to: http://%SERVER_IP%
echo    3. They can now use your ComLab system!
echo.
echo 📋 Share this information with your classmates:
echo    "Connect to ComLab server at: http://%SERVER_IP%"
echo.
echo 🔧 Server Management:
echo    - Monitor server: http://localhost/check_network_status.php
echo    - Server info: http://localhost/lab_server_info.php
echo    - To stop server: Run restore_original_config.bat
echo.
echo 🚀 Your ComLab server is ready for the school lab!
echo.
echo Press any key to open the server info page...
pause >nul
start http://localhost/lab_server_info.php
