<?php

namespace App\Domain\Assistant\Models;

use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssistantSession extends Model
{
    use HasFactory, HasHashId;

    protected $table = 'assistant_sessions';

    public $timestamps = false;

    protected $fillable = ['conversation_id', 'started_at', 'last_message_at'];

    protected $casts = [
        'started_at'      => 'datetime',
        'last_message_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AssistantMessage::class, 'session_id');
    }
}
