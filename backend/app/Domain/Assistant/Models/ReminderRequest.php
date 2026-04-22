<?php

namespace App\Domain\Assistant\Models;

use App\Models\User;
use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderRequest extends Model
{
    use HasFactory, HasHashId;

    protected $table = 'reminder_requests';

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'content',
        'suggested_event_at',
        'status',
        'event_id',
        'message_id',
    ];

    protected $casts = [
        'suggested_event_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(AssistantEvent::class, 'event_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(AssistantMessage::class, 'message_id');
    }
}
