<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use App\Models\ActivityLog;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup {--manual : Indicates if this was triggered manually}';
    protected $description = 'Creates a daily backup of the MySQL database and deletes backups older than 30 days';

    public function handle()
    {
        $filename = "rmph_backup_" . Carbon::now()->format('Y-m-d_H-i-s') . ".sql";
        $storagePath = storage_path('app/backups');

        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $dbName = config('database.connections.mysql.database', env('DB_DATABASE', 'supply_hub'));
        $username = config('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        $password = config('database.connections.mysql.password', env('DB_PASSWORD', ''));
        $host = config('database.connections.mysql.host', env('DB_HOST', '127.0.0.1'));
        $port = config('database.connections.mysql.port', env('DB_PORT', '3306'));

        // Locate mysqldump binary (prefer Laragon MySQL if present)
        $mysqlDumpPath = 'mysqldump';
        $laragonDump = 'C:\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysqldump.exe';
        if (file_exists($laragonDump)) {
            $mysqlDumpPath = '"' . $laragonDump . '"';
        }

        $targetFile = $storagePath . DIRECTORY_SEPARATOR . $filename;
        $passwordString = ($password !== '' && $password !== null) ? "--password=" . escapeshellarg($password) . " " : "";
        $command = "{$mysqlDumpPath} --host={$host} --port={$port} --user={$username} {$passwordString}--default-character-set=utf8mb4 {$dbName} > " . escapeshellarg($targetFile);

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        // Verify dump file integrity (must exist and be at least 1KB)
        if (!file_exists($targetFile) || filesize($targetFile) < 1024) {
            $err = implode("\n", $output);
            if (file_exists($targetFile)) {
                @unlink($targetFile);
            }
            $this->error("Database backup failed: " . ($err ?: 'Generated file was empty or corrupted.'));
            Log::error("Database backup failed: " . ($err ?: 'Empty file.'));
            ActivityLog::log('Database Backup', "Automated backup failed.");
            return 1;
        }

        $sizeKb = round(filesize($targetFile) / 1024, 2);
        $this->info("Backup created successfully: {$filename} ({$sizeKb} KB)");
        Log::info("Database backup ran successfully: {$filename} ({$sizeKb} KB)");

        $source = $this->option('manual') ? 'Manual' : 'Automated';
        ActivityLog::log('Database Backup', "{$source} backup created: {$filename} ({$sizeKb} KB)");

        // AUTO-CLEANUP: Delete backups older than 30 days
        $files = File::files($storagePath);
        $deletedCount = 0;

        foreach ($files as $file) {
            if (Carbon::createFromTimestamp($file->getMTime())->diffInDays(Carbon::now()) > 30) {
                File::delete($file);
                $deletedCount++;
            }
        }

        if ($deletedCount > 0) {
            $this->info("Cleaned up {$deletedCount} old backup(s).");
            Log::info("Cleaned up {$deletedCount} old database backup(s).");
        }

        return 0;
    }
}
