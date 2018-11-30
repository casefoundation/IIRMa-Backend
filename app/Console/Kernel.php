<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\AnalyzeImpactspace',
        Commands\AnalyzeCrunchbase::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        if (env('IMPACTSPACE_KEY', false) != false) {
            $schedule
                ->command('impactspace:analyze')
                ->cron('0 0 * * 0')
                ->sendOutputTo('/var/log/impactspace.log')
                ->emailOutputTo(env('CRON_EMAIL', 'maintenance@casefoundation.org'));
        }
        if (env('CRUNCHBASE_KEY', false) != false) {
            $schedule
                ->command('crunchbase:analyze')
                ->cron('0 0 * * 1')
                ->sendOutputTo('/var/log/crunchbase.log')
                ->emailOutputTo(env('CRON_EMAIL', 'maintenance@casefoundation.org'));
        }
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
