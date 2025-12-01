<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\Hold;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // Delete expired holds every minute
        $schedule->call(function () {
            Hold::where('expires_at', '<', now())->delete();
        })->everyMinute();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
