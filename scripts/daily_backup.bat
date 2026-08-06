@echo off
REM Schedule this in Windows Task Scheduler to run daily (e.g. 1:00 AM).
REM Action: start this .bat
cd /d C:\xampp\htdocs\barangay_default
C:\xampp\php\php.exe scripts\daily_backup.php
exit /b %ERRORLEVEL%
