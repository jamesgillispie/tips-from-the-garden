<?php

use Illuminate\Support\Facades\Schedule;

// Browser-channel users see the privacy preferences panel when they return.
// People who declined optional cookies receive the same check-in by email.
Schedule::command('ai:send-check-ins')
    ->monthlyOn(1, '09:00')
    ->withoutOverlapping();

// Console routes / closures can live here. The pipeline:run command
// is a class — see app/Console/Commands/RunPipeline.php.
