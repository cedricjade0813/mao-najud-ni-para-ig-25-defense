@echo off
echo ========================================
echo    ComLab School Lab Server Setup
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

echo [STEP 1] Getting network information...
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4 Address"') do (
    set "IP=%%a"
    set "IP=!IP: =!"
    goto :found
)
:found

echo [STEP 2] Configuring Windows Firewall for network access...

REM Allow Apache through Windows Firewall
netsh advfirewall firewall add rule name="ComLab Server HTTP" dir=in action=allow protocol=TCP localport=80
netsh advfirewall firewall add rule name="ComLab Server HTTP Out" dir=out action=allow protocol=TCP localport=80

echo [STEP 3] Stopping Apache if running...
taskkill /F /IM httpd.exe >nul 2>&1

echo [STEP 4] Creating school lab server configuration...

REM Create the school lab virtual host file
(
echo # ComLab School Lab Server Configuration
echo # This allows access from ALL computers in the lab
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

echo [STEP 5] Updating main Apache configuration...

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

echo [STEP 6] Testing Apache configuration...
"C:\xampp\apache\bin\httpd.exe" -t
if %errorLevel% neq 0 (
    echo [ERROR] Apache configuration test failed!
    echo Restoring original configuration...
    copy "C:\xampp\apache\conf\httpd.conf.backup" "C:\xampp\apache\conf\httpd.conf" >nul
    pause
    exit /b 1
)

echo [STEP 7] Starting Apache server...
start "" "C:\xampp\apache\bin\httpd.exe"

echo [STEP 8] Getting current IP address...
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4 Address"') do (
    set "SERVER_IP=%%a"
    set "SERVER_IP=!SERVER_IP: =!"
    goto :ipfound
)
:ipfound

echo.
echo ========================================
echo    School Lab Server Setup Complete!
echo ========================================
echo.
echo 🎉 Your ComLab server is now running!
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
echo    - To stop server: Close this window or run restore_original_config.bat
echo    - To restart: Run this script again
echo    - To check status: Visit http://localhost/get_my_ip.php
echo.
echo Your ComLab server is ready for the school lab! 🚀
echo.
pause
