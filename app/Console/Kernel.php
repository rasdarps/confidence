<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Attempt auto-send every 10 minutes from configured hour to end of day.
        // Controller-level duplicate guard ensures only one email per day.
        $emailHour = (int) config('pos.daily_email_report_time', 18);
        $emailHour = max(0, min(23, $emailHour));
        $schedule->command('email:send-daily-transaction')
            ->everyTenMinutes()
            ->between(str_pad((string) $emailHour, 2, '0', STR_PAD_LEFT) . ':00', '23:59')
            ->timezone(config('app.timezone'))
            ->description('Send daily transaction email report')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
