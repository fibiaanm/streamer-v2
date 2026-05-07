<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FailedJobsSeeder extends Seeder
{
    private const JOBS = [
        ['class' => 'App\\Domain\\Assistant\\Jobs\\FireEventReminder',   'queue' => 'assistant'],
        ['class' => 'App\\Domain\\Assistant\\Jobs\\SweepTokenUsageRollupJob', 'queue' => 'default'],
        ['class' => 'App\\Domain\\Assistant\\Jobs\\ProcessMemoryWorker', 'queue' => 'assistant'],
        ['class' => 'App\\Domain\\Notifications\\Jobs\\SendPushNotification', 'queue' => 'default'],
        ['class' => 'App\\Domain\\Workspace\\Jobs\\SyncWorkspacePermissions', 'queue' => 'default'],
    ];

    private const EXCEPTIONS = [
        "Illuminate\\Database\\QueryException: SQLSTATE[23505]: Unique violation: 7 ERROR: duplicate key value violates unique constraint\nStack trace:\n#0 vendor/laravel/framework/src/Illuminate/Database/Connection.php(760): ...",
        "RuntimeException: Redis connection refused: tcp://redis:6379\nStack trace:\n#0 vendor/laravel/framework/src/Illuminate/Redis/Connections/PhpRedisConnection.php(82): ...",
        "Illuminate\\Database\\Eloquent\\ModelNotFoundException: No query results for model [App\\Models\\User] 9999\nStack trace:\n#0 vendor/laravel/framework/src/Illuminate/Database/Eloquent/Builder.php(434): ...",
        "GuzzleHttp\\Exception\\ConnectException: cURL error 28: Operation timed out after 30001 milliseconds\nStack trace:\n#0 vendor/guzzlehttp/guzzle/src/Handler/CurlHandler.php(45): ...",
        "ErrorException: Typed property must not be accessed before initialization\nStack trace:\n#0 app/Domain/Assistant/Jobs/FireEventReminder.php(42): ...",
    ];

    public function run(): void
    {
        // skip if already seeded
        if (DB::table('failed_jobs')->count() > 0) {
            $this->command->info('FailedJobsSeeder: already has data, skipping.');
            return;
        }

        $rows = [];

        // Generate 90 days of history with realistic spike patterns
        for ($daysAgo = 90; $daysAgo >= 0; $daysAgo--) {
            $base   = now()->subDays($daysAgo);
            $count  = $this->failuresForDay($daysAgo);

            for ($i = 0; $i < $count; $i++) {
                $job      = self::JOBS[array_rand(self::JOBS)];
                $uuid     = Str::uuid()->toString();
                $failedAt = $base->copy()->addMinutes(rand(0, 1439));

                $rows[] = [
                    'uuid'       => $uuid,
                    'connection' => 'database',
                    'queue'      => $job['queue'],
                    'payload'    => json_encode([
                        'uuid'        => $uuid,
                        'displayName' => $job['class'],
                        'job'         => 'Illuminate\\Queue\\CallQueuedHandler@call',
                        'data'        => ['commandName' => $job['class'], 'command' => base64_encode(serialize([]))],
                    ]),
                    'exception'  => self::EXCEPTIONS[array_rand(self::EXCEPTIONS)],
                    'failed_at'  => $failedAt,
                ];
            }
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('failed_jobs')->insert($chunk);
        }

        $this->command->info('FailedJobsSeeder: inserted ' . count($rows) . ' records.');
    }

    private function failuresForDay(int $daysAgo): int
    {
        // incident spikes at ~15d, ~45d, ~75d ago
        $spikes = [15 => 12, 14 => 8, 16 => 6, 45 => 15, 44 => 9, 46 => 7, 75 => 10, 74 => 5];

        if (isset($spikes[$daysAgo])) {
            return $spikes[$daysAgo];
        }

        // quiet days: 0-2 failures; noisier days: 1-4
        return $daysAgo % 7 === 0 ? rand(1, 4) : rand(0, 2);
    }
}
