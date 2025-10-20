@echo off
echo ========================================
echo    Create ComLab Portable Package
echo ========================================
echo.

set PACKAGE_NAME=ComLab_Portable_%date:~-4,4%%date:~-10,2%%date:~-7,2%
set PACKAGE_DIR=%~dp0%PACKAGE_NAME%

echo [STEP 1] Creating portable package directory...
if exist "%PACKAGE_DIR%" rmdir /s /q "%PACKAGE_DIR%"
mkdir "%PACKAGE_DIR%"

echo [STEP 2] Copying ComLab project files...
xcopy "%~dp0*" "%PACKAGE_DIR%\" /E /I /Y /EXCLUDE:exclude_list.txt

echo [STEP 3] Creating setup instructions...
(
echo # ComLab Portable Server Package
echo.
echo ## 🚀 Quick Setup on ANY Laptop:
echo.
echo ### Step 1: Copy Files
echo 1. Copy this entire folder to the new laptop
echo 2. Place it anywhere you want ^(Desktop, Documents, etc.^)
echo.
echo ### Step 2: Run Auto-Setup
echo 1. Right-click on AUTO_SETUP_NEW_LAPTOP.bat
echo 2. Select "Run as administrator"
echo 3. Wait for setup to complete
echo 4. Done! Your server is ready!
echo.
echo ## 🌐 Access Your Website:
echo - From this computer: http://localhost
echo - From other computers: http://[your-ip-address]
echo - Find your IP: http://localhost/get_my_ip.php
echo.
echo ## 📁 Files Included:
echo - AUTO_SETUP_NEW_LAPTOP.bat ^(Main setup script^)
echo - restore_original_config.bat ^(Restore XAMPP^)
echo - get_my_ip.php ^(Find your IP address^)
echo - lab_server_info.php ^(Server information^)
echo - check_network_status.php ^(Monitor server^)
echo - STUDENT_CONNECTION_GUIDE.md ^(Instructions for classmates^)
echo - All your ComLab project files
echo.
echo ## 🔧 Requirements:
echo - XAMPP installed on target computer
echo - Administrator privileges
echo - Windows operating system
echo.
echo ## 🎯 What This Package Does:
echo - Automatically configures Apache for network access
echo - Sets up Windows Firewall rules
echo - Creates virtual host configuration
echo - Starts the ComLab server
echo - Provides connection information
echo.
echo ## 📞 Need Help?
echo - Check the setup instructions above
echo - Make sure XAMPP is installed
echo - Run as Administrator
echo.
echo Your ComLab project is now portable! 🚀
) > "%PACKAGE_DIR%\SETUP_INSTRUCTIONS.txt"

echo [STEP 4] Creating exclude list for next time...
(
echo *.log
echo *.tmp
echo .git
echo node_modules
echo uploads\temp
echo exclude_list.txt
) > exclude_list.txt

echo [STEP 5] Creating quick start script...
(
echo @echo off
echo echo ========================================
echo echo    ComLab Quick Start
echo echo ========================================
echo echo.
echo echo This will set up ComLab server on this laptop.
echo echo.
echo echo Requirements:
echo echo - XAMPP must be installed
echo echo - Run as Administrator
echo echo.
echo echo Press any key to continue or close this window to cancel...
echo pause ^>nul
echo.
echo echo Starting ComLab auto-setup...
echo call "%%~dp0AUTO_SETUP_NEW_LAPTOP.bat"
) > "%PACKAGE_DIR%\QUICK_START.bat"

echo.
echo ========================================
echo    Package Created Successfully!
echo ========================================
echo.
echo 📦 Package location: %PACKAGE_DIR%
echo.
echo 🚀 To deploy on another laptop:
echo 1. Copy the entire package folder to the new laptop
echo 2. Right-click on AUTO_SETUP_NEW_LAPTOP.bat
echo 3. Select "Run as administrator"
echo 4. Wait for setup to complete
echo 5. Your server is ready!
echo.
echo 📋 Package Contents:
echo - AUTO_SETUP_NEW_LAPTOP.bat ^(Main setup script^)
echo - QUICK_START.bat ^(Easy start script^)
echo - SETUP_INSTRUCTIONS.txt ^(Detailed instructions^)
echo - All your ComLab project files
echo.
echo 🎉 Your ComLab project is now portable! 🚀
echo.
pause
