<?php

namespace SRWieZ\ForgeHeartbeats\Tests\TestClasses;

use Closure;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as IlluminateKernel;

class TestKernel extends IlluminateKernel
{
    protected static array $registeredScheduleCommands = [];

    /**
     * Testbench already bootstrapped the application; re-running the bootstrappers
     * would reload configuration and discard the values set in the TestCase.
     */
    protected $bootstrappers = [];

    public function schedule(Schedule $schedule)
    {
        collect(static::$registeredScheduleCommands)->each(
            fn (Closure $closure) => $closure($schedule)
        );
    }

    public static function registerScheduledTasks(Closure $closure)
    {
        static::$registeredScheduleCommands[] = $closure;
    }

    public static function replaceScheduledTasks(Closure $closure)
    {
        static::clearScheduledCommands();
        static::$registeredScheduleCommands[] = $closure;
    }

    public static function clearScheduledCommands()
    {
        static::$registeredScheduleCommands = [];
    }
}
