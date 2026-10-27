<?php

namespace App\Console\Commands;

use App\Http\Controllers\Backend\DailyTransactionEmailController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendDailyTransactionEmail extends Command
{
    protected $signature = 'email:send-daily-transaction';
    protected $description = 'Send daily transaction email report (sales, expenses, cash shortage)';

    public function handle()
    {
        $this->info('Sending daily transaction email...');

        Cache::put(
            'health:daily-email-scheduler:last_run_at',
            now()->toIso8601String(),
            now()->addDays(2)
        );

        $controller = new DailyTransactionEmailController();
        $result = $controller->sendAuto();

        if (($result['status'] ?? null) === 'sent') {
            $recipient = $result['recipient'] ?? 'recipient';
            $this->info('Daily transaction email sent successfully to ' . $recipient . '.');
            return self::SUCCESS;
        }

        if (($result['status'] ?? null) === 'skipped') {
            $this->warn('Daily transaction email skipped: ' . ($result['message'] ?? 'No action needed') . '.');
            return self::SUCCESS;
        }

        $this->error('Daily transaction email failed: ' . ($result['message'] ?? 'Unknown error') . '.');
        return self::FAILURE;
    }
}
