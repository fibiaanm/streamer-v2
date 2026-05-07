<?php

namespace App\Domain\Assistant\Models;

use App\Models\User;
use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class ReminderRun extends Model
{
    use HasHashId;

    protected $table = 'reminder_runs';

    protected $fillable = [
        'user_id',
        'run_at',
        'kind',
        'job_id',
        'status',
    ];

    protected $casts = [
        'run_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(EventReminder::class, 'reminder_run_id');
    }

    public function cancelIfEmpty(): void
    {
        if ($this->reminders()->exists()) {
            return;
        }

        if ($this->job_id) {
            DB::table('jobs')->where('id', $this->job_id)->delete();
        }

        $this->delete();
    }
}
