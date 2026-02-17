<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'backup:database {--path= : Custom output directory}';

    protected $description = 'Create a local database backup file';

    public function handle(): int
    {
        $connection = (string) config('database.default');
        $config = (array) config("database.connections.$connection");
        $driver = (string) ($config['driver'] ?? '');

        $backupDir = (string) ($this->option('path') ?: storage_path('app/backups/database'));
        File::ensureDirectoryExists($backupDir);

        $timestamp = now()->format('Ymd_His');

        return match ($driver) {
            'sqlite' => $this->backupSqlite($config, $backupDir, $timestamp),
            'mysql', 'mariadb' => $this->backupMysql($config, $backupDir, $timestamp),
            'pgsql' => $this->backupPgsql($config, $backupDir, $timestamp),
            default => $this->unsupportedDriver($driver),
        };
    }

    private function backupSqlite(array $config, string $backupDir, string $timestamp): int
    {
        $database = (string) ($config['database'] ?? '');
        if ($database === '' || $database === ':memory:') {
            $this->error('Cannot back up in-memory sqlite database.');
            return self::FAILURE;
        }

        $filename = "backup_sqlite_{$timestamp}.sqlite";
        $outputPath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        if (! @copy($database, $outputPath)) {
            $this->error('Failed to copy sqlite database file.');
            return self::FAILURE;
        }

        $this->info("Backup created: $outputPath");
        return self::SUCCESS;
    }

    private function backupMysql(array $config, string $backupDir, string $timestamp): int
    {
        $filename = "backup_mysql_{$timestamp}.sql";
        $outputPath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        $command = [
            (string) env('MYSQLDUMP_PATH', 'mysqldump'),
            '--no-defaults',
            '--host=' . ($config['host'] ?? '127.0.0.1'),
            '--port=' . ($config['port'] ?? '3306'),
            '--user=' . ($config['username'] ?? 'root'),
            '--single-transaction',
            '--skip-lock-tables',
            '--result-file=' . $outputPath,
            (string) ($config['database'] ?? ''),
        ];

        $password = (string) ($config['password'] ?? '');
        if ($password !== '') {
            $command[] = '--password=' . $password;
        }

        $process = new Process($command);
        $process->setTimeout(180);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('mysqldump failed: ' . $process->getErrorOutput());
            return self::FAILURE;
        }

        if (! File::exists($outputPath) || File::size($outputPath) <= 0) {
            $this->error('mysqldump completed but backup file is missing/empty.');
            return self::FAILURE;
        }

        $this->info("Backup created: $outputPath");

        return self::SUCCESS;
    }

    private function backupPgsql(array $config, string $backupDir, string $timestamp): int
    {
        $filename = "backup_pgsql_{$timestamp}.sql";
        $outputPath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        $command = [
            (string) env('PG_DUMP_PATH', 'pg_dump'),
            '--host=' . ($config['host'] ?? '127.0.0.1'),
            '--port=' . ($config['port'] ?? '5432'),
            '--username=' . ($config['username'] ?? 'postgres'),
            '--dbname=' . ($config['database'] ?? ''),
            '--no-owner',
            '--no-privileges',
        ];

        $env = [];
        if (! empty($config['password'])) {
            $env['PGPASSWORD'] = (string) $config['password'];
        }

        $process = new Process($command, null, $env);
        $process->setTimeout(180);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('pg_dump failed: ' . $process->getErrorOutput());
            return self::FAILURE;
        }

        File::put($outputPath, $process->getOutput());
        $this->info("Backup created: $outputPath");

        return self::SUCCESS;
    }

    private function unsupportedDriver(string $driver): int
    {
        $this->error("Unsupported database driver for backup: $driver");
        return self::FAILURE;
    }
}
