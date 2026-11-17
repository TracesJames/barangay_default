# Daily backup & restore

## What runs
- Script: `scripts/daily_backup.php`
- Windows helper: `scripts/daily_backup.bat`
- Output folder: `C:\xampp\secure\backups\YYYY-MM-DD\`
  - `barangay_*.sql` — full database dump
  - `uploads_*.zip` — resident/official upload files
  - `backup.log` — run log
- Keeps the last **14** day folders.

## Schedule (Windows Task Scheduler)
Register (Administrator PowerShell):

```powershell
Set-ExecutionPolicy -Scope Process Bypass
cd C:\xampp\htdocs\barangay
.\scripts\register_daily_backup_task.ps1
```

Or create manually:
1. Task Scheduler → Create Basic Task.
2. Trigger: **Daily**, 1:00 AM.
3. Action: **Start a program**
   - Program: `C:\xampp\htdocs\barangay\scripts\daily_backup.bat`

## Manual run
```bat
C:\xampp\php\php.exe C:\xampp\htdocs\barangay\scripts\daily_backup.php
```

## Restore drill (do at least once after setup)
1. Pick a day folder under `C:\xampp\secure\backups\`.
2. Create a test DB (do **not** overwrite production):
   ```bat
   C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS barangay_restore_test"
   ```
3. Import the `.sql` file:
   ```bat
   C:\xampp\mysql\bin\mysql.exe -u root barangay_restore_test < C:\xampp\secure\backups\YYYY-MM-DD\barangay_*.sql
   ```
4. Spot-check: `SHOW TABLES;` and a sample `SELECT COUNT(*) FROM users;`
5. Unzip `uploads_*.zip` into a temp folder and confirm files open.
6. Drop the test DB:
   ```bat
   C:\xampp\mysql\bin\mysql.exe -u root -e "DROP DATABASE barangay_restore_test"
   ```

Do **not** store dumps inside `htdocs` (webroot).
