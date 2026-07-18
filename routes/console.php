<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('reminders:send')->everyMinute();
Schedule::command('backup:run')->daily();
Schedule::command('activities:cleanup')->daily();
