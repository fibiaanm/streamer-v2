<?php

namespace App\Domain\Assistant\Models;

use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class EventReminder extends Model
{
    use HasFactory, HasHashId;

    protected $table = 'event_reminders';

    public $timestamps = false;

    protected $fillable = [
        'event_id',
        'fire_at',
        'message',
        'status',
        'fired_at',
        'job_id',
    ];

    public static function cancelJobsByEventIds(array $eventIds): void
    {
        $jobIds = static::whereIn('event_id', $eventIds)
            ->where('status', 'pending')
            ->whereNotNull('job_id')
            ->pluck('job_id');

        if ($jobIds->isNotEmpty()) {
            DB::table('jobs')->whereIn('id', $jobIds)->delete();
        }
    }

    protected $casts = [
        'fire_at'    => 'datetime',
        'fired_at'   => 'datetime',
        'created_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(AssistantEvent::class, 'event_id');
    }
}
