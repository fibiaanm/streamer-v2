<?php

namespace App\Domain\Assistant\Models;

use App\Models\User;
use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssistantEvent extends Model
{
    use HasFactory, HasHashId, SoftDeletes;

    protected $table = 'assistant_events';

    protected $fillable = [
        'user_id',
        'sender_id',
        'series_id',
        'content',
        'event_at',
        'event_end',
        'occurrence_at',
        'type',
        'recurrence_rule',
        'reminders_template_json',
        'status',
        'referenceable_type',
        'referenceable_id',
    ];

    protected $casts = [
        'event_at'                => 'datetime',
        'event_end'               => 'datetime',
        'occurrence_at'           => 'datetime',
        'reminders_template_json' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(AssistantEvent::class, 'series_id');
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(AssistantEvent::class, 'series_id');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(EventReminder::class, 'event_id');
    }

    public function referenceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isMaster(): bool
    {
        return is_null($this->series_id) && ! is_null($this->recurrence_rule);
    }

    public function isOccurrence(): bool
    {
        return ! is_null($this->series_id);
    }

    public function isSingle(): bool
    {
        return is_null($this->series_id) && is_null($this->recurrence_rule);
    }
}
