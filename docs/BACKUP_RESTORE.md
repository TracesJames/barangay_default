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
1. Open **Task Scheduler** → Create Basic Task.
2. Trigger: **Daily**, 1:00 AM.
3. Action: **Start a program**
   - Program: `C:\xampp\htdocs\barangay_default\scripts\daily_backup.bat`
4. Run whether user is logged on or not (optional).

## Manual run
```bat
C:\xampp\php\php.exe C:\xampp\htdocs\barangay_default\scripts\daily_backup.php
```

## Restore smoke test (quarterly)
1. Pick a day folder under `C:\xampp\secure\backups\`.
2. Create a test DB (e.g. `barangay_restore_test`).
3. Import the `.sql` file via phpMyAdmin or:
   ```bat
   C:\xampp\mysql\bin\mysql.exe -u root barangay_restore_test < barangay_YYYYMMDD_HHMMSS.sql
   ```
4. Unzip `uploads_*.zip` into a temp folder and confirm files open.
5. Drop the test DB after verification.

Do **not** store dumps inside `htdocs` (webroot).
