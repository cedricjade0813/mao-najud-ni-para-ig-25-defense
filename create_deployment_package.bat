@echo off
echo ========================================
echo    Create ComLab Deployment Package
echo ========================================
echo.

set PACKAGE_NAME=ComLab_Portable_%date:~-4,4%%date:~-10,2%%date:~-7,2%
set PACKAGE_DIR=%~dp0%PACKAGE_NAME%

echo [STEP 1] Creating deployment package directory...
if exist "%PACKAGE_DIR%" rmdir /s /q "%PACKAGE_DIR%"
mkdir "%PACKAGE_DIR%"

echo [STEP 2] Copying ComLab project files...
xcopy "%~dp0*" "%PACKAGE_DIR%\" /E /I /Y /EXCLUDE:exclude_list.txt

echo [STEP 3] Creating setup instructions...
(
echo # ComLab Portable Server Package
echo.
echo ## Quick Setup:
echo 1. Copy this entire folder to C:\xampp\htdocs\comlab
echo 2. Right-click setup_portable_server.bat
echo 3. Select "Run as administrator"
echo 4. Done! Your server is ready.
echo.
echo ## Access Your Website:
echo - http://localhost
echo - http://[your-ip-address]
echo - Find your IP: http://localhost/get_my_ip.php
echo.
echo ## Files Included:
echo - setup_portable_server.bat ^(Main setup script^)
echo - restore_original_config.bat ^(Restore XAMPP^)
echo - get_my_ip.php ^(Find your IP address^)
echo - PORTABLE_SETUP_README.md ^(Detailed instructions^)
echo - All your ComLab project files
echo.
echo ## Requirements:
echo - XAMPP installed on target computer
echo - Administrator privileges
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
) > exclude_list.txt

echo.
echo ========================================
echo    Package Created Successfully!
echo ========================================
echo.
echo Package location: %PACKAGE_DIR%
echo.
echo To deploy on another laptop:
echo 1. Copy the entire package folder
echo 2. Place it in C:\xampp\htdocs\comlab
echo 3. Run setup_portable_server.bat as Administrator
echo.
echo Your ComLab project is now portable! 🚀
echo.
pause
