# Register Windows Task Scheduler job for daily database backup.
# Run in PowerShell as Administrator:
#   Set-ExecutionPolicy -Scope Process Bypass
#   .\scripts\register_daily_backup_task.ps1

$TaskName = 'BarangayPortalDailyBackup'
$BatPath = 'C:\xampp\htdocs\barangay_default\scripts\daily_backup.bat'

if (-not (Test-Path $BatPath)) {
    Write-Error "Batch file not found: $BatPath"
    exit 1
}

$Action = New-ScheduledTaskAction -Execute $BatPath -WorkingDirectory 'C:\xampp\htdocs\barangay_default'
$Trigger = New-ScheduledTaskTrigger -Daily -At 1:00AM
$Settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
$Principal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount -RunLevel Highest

Register-ScheduledTask -TaskName $TaskName -Action $Action -Trigger $Trigger -Settings $Settings -Principal $Principal -Force | Out-Null

Write-Host "Scheduled task '$TaskName' registered (daily 1:00 AM)."
Write-Host "Backups: C:\xampp\secure\backups\YYYY-MM-DD\"
Write-Host "Test now: C:\xampp\php\php.exe scripts\daily_backup.php"
