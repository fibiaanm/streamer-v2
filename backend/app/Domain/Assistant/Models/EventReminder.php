<?php

namespace App\Domain\Assistant\Models;

use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

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
