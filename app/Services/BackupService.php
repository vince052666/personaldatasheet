<?php

namespace App\Services;

use App\Models\BackupLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BackupService
{
    private const BACKUP_DISK = 'backups';

    public function backupDatabase(): BackupLog
    {
        $log = BackupLog::create([
            'backup_type' => 'database',
            'status' => 'started',
            'started_at' => now(),
        ]);

        try {
            $filename = 'db-backup-' . now()->format('Y-m-d-His') . '.sql';
            $path = storage_path('app/backups/' . $filename);

            // Ensure directory exists
            File::ensureDirectoryExists(dirname($path));

            // Use Laravel's DB connection for backup
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $host = config('database.connections.mysql.host');

            // Create my.cnf file with credentials for security
            // Set umask to ensure file is created with correct permissions
            $oldUmask = umask(0077); // Creates files with 0600 permissions
            $cnfPath = storage_path('app/backups/.my.cnf');
            $cnfContent = "[client]\nuser={$username}\npassword={$password}\nhost={$host}\n";
            file_put_contents($cnfPath, $cnfContent, LOCK_EX);
            umask($oldUmask); // Restore original umask

            // Generate backup using mysqldump with config file
            $command = sprintf(
                'mysqldump --defaults-extra-file=%s %s > %s',
                escapeshellarg($cnfPath),
                escapeshellarg($database),
                escapeshellarg($path)
            );

            $process = Process::fromShellCommandline($command);
            $process->setTimeout(3600); // 1 hour timeout
            $process->run();

            // Remove credentials file immediately
            @unlink($cnfPath);

            if (!$process->isSuccessful()) {
                throw new \Exception($process->getErrorOutput());
            }

            // Compress the backup
            $compressedPath = $path . '.gz';
            $this->compressFile($path, $compressedPath);
            
            // Calculate checksum
            $checksum = hash_file('sha256', $compressedPath);

            // Update log
            $log->update([
                'status' => 'completed',
                'file_path' => $compressedPath,
                'file_size' => filesize($compressedPath),
                'checksum' => $checksum,
                'completed_at' => now(),
            ]);

            // Clean up uncompressed file
            unlink($path);

            return $log;
        } catch (\Exception $e) {
            // Ensure credentials file is removed on error
            if (isset($cnfPath) && file_exists($cnfPath)) {
                @unlink($cnfPath);
            }
            
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }

    public function backupFiles(array $directories = null): BackupLog
    {
        $log = BackupLog::create([
            'backup_type' => 'files',
            'status' => 'started',
            'started_at' => now(),
        ]);

        try {
            $directories = $directories ?? [
                storage_path('app/uploads'),
                storage_path('app/documents'),
            ];

            $filename = 'files-backup-' . now()->format('Y-m-d-His') . '.tar.gz';
            $path = storage_path('app/backups/' . $filename);

            File::ensureDirectoryExists(dirname($path));

            // Create tar archive
            $dirList = implode(' ', array_map('escapeshellarg', $directories));
            $command = sprintf('tar -czf %s %s', escapeshellarg($path), $dirList);

            $process = Process::fromShellCommandline($command);
            $process->setTimeout(7200); // 2 hours timeout
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \Exception($process->getErrorOutput());
            }

            $checksum = hash_file('sha256', $path);

            $log->update([
                'status' => 'completed',
                'file_path' => $path,
                'file_size' => filesize($path),
                'checksum' => $checksum,
                'completed_at' => now(),
            ]);

            return $log;
        } catch (\Exception $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }

    public function backupFull(): array
    {
        return [
            'database' => $this->backupDatabase(),
            'files' => $this->backupFiles(),
        ];
    }

    public function restoreDatabase(string $backupPath): bool
    {
        if (!file_exists($backupPath)) {
            throw new \Exception("Backup file not found: {$backupPath}");
        }

        // Decompress if needed
        $sqlPath = $backupPath;
        if (str_ends_with($backupPath, '.gz')) {
            $sqlPath = str_replace('.gz', '', $backupPath);
            $this->decompressFile($backupPath, $sqlPath);
        }

        try {
            $command = sprintf(
                'mysql -u%s -p%s %s < %s',
                config('database.connections.mysql.username'),
                config('database.connections.mysql.password'),
                config('database.connections.mysql.database'),
                $sqlPath
            );

            $process = Process::fromShellCommandline($command);
            $process->setTimeout(3600);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \Exception($process->getErrorOutput());
            }

            // Clean up decompressed file if it was created
            if ($sqlPath !== $backupPath) {
                unlink($sqlPath);
            }

            return true;
        } catch (\Exception $e) {
            if ($sqlPath !== $backupPath && file_exists($sqlPath)) {
                unlink($sqlPath);
            }
            throw $e;
        }
    }

    public function verifyBackup(BackupLog $log): bool
    {
        if (!file_exists($log->file_path)) {
            return false;
        }

        $currentChecksum = hash_file('sha256', $log->file_path);
        return $currentChecksum === $log->checksum;
    }

    public function cleanOldBackups(int $daysToKeep = 30): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        $oldBackups = BackupLog::where('created_at', '<', $cutoffDate)
            ->where('status', 'completed')
            ->get();

        $count = 0;
        foreach ($oldBackups as $backup) {
            if (file_exists($backup->file_path)) {
                unlink($backup->file_path);
                $count++;
            }
            $backup->delete();
        }

        return $count;
    }

    private function compressFile(string $sourcePath, string $destPath): void
    {
        $source = fopen($sourcePath, 'rb');
        $dest = gzopen($destPath, 'wb9');

        while (!feof($source)) {
            gzwrite($dest, fread($source, 1024 * 512));
        }

        fclose($source);
        gzclose($dest);
    }

    private function decompressFile(string $sourcePath, string $destPath): void
    {
        $source = gzopen($sourcePath, 'rb');
        $dest = fopen($destPath, 'wb');

        while (!gzeof($source)) {
            fwrite($dest, gzread($source, 1024 * 512));
        }

        gzclose($source);
        fclose($dest);
    }

    public function getBackupHistory(int $limit = 50)
    {
        return BackupLog::orderBy('started_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
