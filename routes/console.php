<?php

use Illuminate\Support\Facades\Schedule;

// Console routes / closures can live here. The pipeline:run command
// is a class — see app/Console/Commands/RunPipeline.php.

// Keep failed_jobs from growing without bound. A week is long enough to
// investigate a failure and short enough that the table stays small.
// Requires a scheduler entry on the mini: `php artisan schedule:run` every
// minute (see DEPLOYMENT.md).
Schedule::command('queue:prune-failed --hours=168')->daily();

// Expired password-reset rows.
Schedule::command('auth:clear-resets')->daily();
