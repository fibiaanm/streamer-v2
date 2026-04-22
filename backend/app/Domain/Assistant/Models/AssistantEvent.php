<?php

namespace App\Domain\Assistant\Models;

use App\Models\User;
use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssistantEvent extends Model
{
    use HasFactory, HasHashId, SoftDeletes;

    protected $table = 'assistant_events';

    protected $fillable = [
        'user_id',
        'sender_id',
        'content',
        'event_at',
        'event_end',
        'type',
        'recurrence_rule',
        'status',
    ];

    protected $casts = [
        'event_at'  => 'datetime',
        'event_end' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(EventReminder::class, 'event_id');
    }
}
