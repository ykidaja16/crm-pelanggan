<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanupAuditLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:cleanup {--days=30 : Number of days to keep}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old audit log files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->option('days');
        $logPath = storage_path('logs');
        $cutoffDate = now()->subDays($days);

        $this->info("Cleaning up audit logs older than {$days} days...");

        $files = File::files($logPath);
        $deletedCount = 0;

        foreach ($files as $file) {
            $filename = $file->getFilename();

            // Only process audit log files
            if (str_starts_with($filename, 'audit-')) {
                $fileModified = now()->createFromTimestamp($file->getMTime());

                if ($fileModified->lt($cutoffDate)) {
                    File::delete($file->getPathname());
                    $this->info("Deleted: {$filename}");
                    $deletedCount++;
                }
            }
        }

        $this->info("Cleanup completed. Deleted {$deletedCount} old audit log files.");

        return Command::SUCCESS;
    }
}
