<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class VerifyLatestBackupCommand extends Command
{
    protected $signature = 'backup:verify-latest {--path= : Backup directory to inspect}';

    protected $description = 'Verify that the latest local backup file exists and is readable';

    public function handle(): int
    {
        $backupDir = (string) ($this->option('path') ?: storage_path('app/backups/database'));

        if (! File::isDirectory($backupDir)) {
            $this->error("Backup directory does not exist: $backupDir");
            return self::FAILURE;
        }

        $files = collect(File::files($backupDir))
            ->sortByDesc(fn($file) => $file->getMTime())
            ->values();

        if ($files->isEmpty()) {
            $this->error('No backup files found.');
            return self::FAILURE;
        }

        $latest = $files->first();
        $size = (int) $latest->getSize();

        if ($size <= 0) {
            $this->error("Latest backup is empty: {$latest->getFilename()}");
            return self::FAILURE;
        }

        $extension = strtolower((string) $latest->getExtension());
        if ($extension === 'sql') {
            $sample = File::get($latest->getPathname(), true);
            $sample = mb_strtolower(substr((string) $sample, 0, 4096));
            $looksValid = str_contains($sample, 'create table')
                || str_contains($sample, 'insert into')
                || str_contains($sample, 'drop table');

            if (! $looksValid) {
                $this->error("Latest SQL backup looks invalid: {$latest->getFilename()}");
                return self::FAILURE;
            }
        }

        $this->info('Latest backup verified:');
        $this->line('File: ' . $latest->getFilename());
        $this->line('Size: ' . number_format($size) . ' bytes');
        $this->line('Modified: ' . date('Y-m-d H:i:s', $latest->getMTime()));

        return self::SUCCESS;
    }
}
