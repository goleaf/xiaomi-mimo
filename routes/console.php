<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('reminders:send')->everyMinute()->withoutOverlapping(5);
Schedule::command('backup:run')->daily();
Schedule::command('activities:cleanup')->daily();
Schedule::command('tasks:recurring')->everyMinute()->withoutOverlapping(5);
