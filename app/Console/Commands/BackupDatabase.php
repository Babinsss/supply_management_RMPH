<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class BackupDatabase extends Command
{
    // The command you will type to run it manually
    protected $signature = 'db:backup';
    protected $description = 'Creates a daily backup of the MySQL database and deletes backups older than 30 days';

    public function handle()
    {
        // 1. Create a timestamped filename
        $filename = "rmph_backup_" . Carbon::now()->format('Y-m-d_H-i-s') . ".sql";
        $storagePath = storage_path('app/backups');

        // 2. Create the backups folder if it doesn't exist
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        // 3. Get database credentials from your .env file
        $dbName = env('DB_DATABASE');
        $username = env('DB_USERNAME');
        $password = env('DB_PASSWORD');

        // 4. Point to XAMPP's mysqldump executable
        $mysqlDumpPath = 'C:\xampp\mysql\bin\mysqldump.exe';

        // 5. Build and execute the command
        $passwordString = $password ? "--password={$password} " : "";
        $command = "{$mysqlDumpPath} --user={$username} {$passwordString}{$dbName} > {$storagePath}\\{$filename}";
        
        exec($command);

        // 6. Log successful creation
        $this->info("Backup created successfully: {$filename}");
        Log::info("Database backup ran successfully: {$filename}");

        // ==========================================
        // 7. AUTO-CLEANUP: Delete backups older than 30 days
        // ==========================================
        $files = File::files($storagePath);
        $deletedCount = 0;

        foreach ($files as $file) {
            // Check if the file's last modified time is older than 30 days
            if (Carbon::createFromTimestamp($file->getMTime())->diffInDays(Carbon::now()) > 30) {
                File::delete($file);
                $deletedCount++;
            }
        }

        if ($deletedCount > 0) {
            $this->info("Cleaned up {$deletedCount} old backup(s).");
            Log::info("Cleaned up {$deletedCount} old database backup(s).");
        }
    }
}