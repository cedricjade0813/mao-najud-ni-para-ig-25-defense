@echo off
echo ========================================
echo    ComLab Quick Start
echo ========================================
echo.
echo This will set up ComLab server on this laptop.
echo.
echo Requirements:
echo - XAMPP must be installed
echo - Run as Administrator
echo.
echo Press any key to continue or close this window to cancel...
pause >nul

echo Starting ComLab auto-setup...
call "%~dp0AUTO_SETUP_NEW_LAPTOP.bat"
