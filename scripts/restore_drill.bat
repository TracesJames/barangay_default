@echo off
REM Staff backup restore drill (uses a temporary DB, not live barangay).
REM Requires MariaDB root password interactively.
setlocal
set MYSQL="C:\Program Files\MariaDB 13.0\bin\mysql.exe"
set HOST=127.0.0.1
set PORT=3000
set DRILL_DB=barangay_restore_drill

if "%~1"=="" (
  echo Usage: restore_drill.bat path\to\barangay_YYYYMMDD_HHMMSS.sql
  echo Example: restore_drill.bat C:\xampp\secure\backups\2026-11-17\barangay_20261117_061655.sql
  exit /b 1
)

set DUMP=%~1
if not exist "%DUMP%" (
  echo Dump not found: %DUMP%
  exit /b 1
)

echo Creating %DRILL_DB% ...
%MYSQL% -h %HOST% -P %PORT% -u root -p -e "DROP DATABASE IF EXISTS %DRILL_DB%; CREATE DATABASE %DRILL_DB% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if errorlevel 1 exit /b 1

echo Importing %DUMP% ...
%MYSQL% -h %HOST% -P %PORT% -u root -p %DRILL_DB% < "%DUMP%"
if errorlevel 1 exit /b 1

echo Counts:
%MYSQL% -h %HOST% -P %PORT% -u root -p %DRILL_DB% -e "SELECT COUNT(*) AS barangays FROM barangay_information; SELECT COUNT(*) AS users FROM users; SELECT COUNT(*) AS surveys FROM nutrition_household_survey;"

echo.
echo Drill DB is ready: %DRILL_DB%
echo Drop when done: %MYSQL% -h %HOST% -P %PORT% -u root -p -e "DROP DATABASE %DRILL_DB%;"
endlocal
