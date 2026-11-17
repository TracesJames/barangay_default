@echo off
cd /d C:\xampp\htdocs\barangay
C:\xampp\php\php.exe scripts\mysql_health_watchdog.php --quiet
exit /b %ERRORLEVEL%
