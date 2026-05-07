<?php

namespace App\Console\Commands;

use App\Domain\Assistant\Jobs\FireReminderRun;
use App\Domain\Assistant\Models\ReminderRun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FireReminderRunCommand extends Command
{
    protected $signature   = 'reminder:fire {job_id : ID del job en la tabla jobs}';
    protected $description = 'Dispara un FireReminderRun por job_id (testing local)';

    public function handle(): int
    {
        $jobId = (string) $this->argument('job_id');
        $run   = ReminderRun::where('job_id', $jobId)->first();

        if (! $run) {
            $this->error("No se encontró ningún ReminderRun con job_id={$jobId}.");
            return self::FAILURE;
        }

        $this->line("Run #{$run->id} · kind={$run->kind} · status={$run->status} · run_at={$run->run_at}");

        if ($run->status !== 'pending') {
            if (! $this->confirm("El run ya está en estado '{$run->status}'. ¿Reejecutar?")) {
                return self::SUCCESS;
            }
            $run->update(['status' => 'pending']);
        }

        (new FireReminderRun($run->id))->handle();

        if ($run->job_id) {
            DB::table('jobs')->where('id', $run->job_id)->delete();
            $this->line("Job #{$run->job_id} eliminado de la tabla jobs.");
        }

        $this->info('Done.');
        return self::SUCCESS;
    }
}
