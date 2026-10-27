<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CheckDailyEmailSchedulerHealth extends Command
{
    protected $signature = 'health:daily-email-scheduler {--max-age=20 : Max allowed age in minutes for scheduler heartbeat}';

    protected $description = 'Check health of daily transaction email scheduler heartbeat';

    public function handle()
    {
        $maxAge = (int) $this->option('max-age');
        if ($maxAge <= 0) {
            $maxAge = 20;
        }

        $enabled = filter_var(config('pos.enable_daily_email_report', false), FILTER_VALIDATE_BOOLEAN);
        $lastRunRaw = Cache::get('health:daily-email-scheduler:last_run_at');

        if (!$lastRunRaw) {
            $this->warn('Scheduler heartbeat has never been recorded.');
            $this->line('Run "php artisan email:send-daily-transaction" once or ensure "php artisan schedule:run" is executing every minute.');
            return self::FAILURE;
        }

        try {
            $lastRun = Carbon::parse($lastRunRaw);
        } catch (\Throwable $e) {
            $this->error('Scheduler heartbeat value is invalid: ' . $lastRunRaw);
            return self::FAILURE;
        }

        $ageMinutes = $lastRun->diffInMinutes(now());
        $status = $ageMinutes <= $maxAge ? 'OK' : 'WARN';

        $this->line('Daily Email Feature Enabled: ' . ($enabled ? 'yes' : 'no'));
        $this->line('Last Scheduler Heartbeat: ' . $lastRun->format('Y-m-d H:i:s'));
        $this->line('Heartbeat Age (minutes): ' . $ageMinutes);
        $this->line('Threshold (minutes): ' . $maxAge);

        if ($status === 'OK') {
            $this->info('Health Status: OK');
            return self::SUCCESS;
        }

        $this->warn('Health Status: WARN - scheduler heartbeat is stale.');
        $this->line('Verify cron/Task Scheduler is running "php artisan schedule:run" every minute.');
        return self::FAILURE;
    }
}
