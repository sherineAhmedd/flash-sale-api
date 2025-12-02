<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
   
    protected function schedule(Schedule $schedule)
    {
       
        $schedule->command('holds:release-expired')->everyMinute();
    }

    protected function commands()
    {
        // Load all custom commands in app/Console/Commands
        $this->load(__DIR__.'/Commands');

        // Optional: include routes/console.php commands
        require base_path('routes/console.php');
    }
}
