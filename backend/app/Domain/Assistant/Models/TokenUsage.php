<?php

namespace App\Domain\Assistant\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TokenUsage extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'conversation_id',
        'session_id',
        'type',
        'provider',
        'model',
        'input_tokens',
        'output_tokens',
        'units',
        'iteration',
        'request_id',
        'metadata_json',
        'created_at',
    ];

    protected $casts = [
        'metadata_json' => 'array',
        'created_at'    => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AssistantSession::class, 'session_id');
    }
}
