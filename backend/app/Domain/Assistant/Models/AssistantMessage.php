<?php

namespace App\Domain\Assistant\Models;

use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class AssistantMessage extends Model
{
    use HasFactory, HasHashId, InteractsWithMedia;

    protected $table = 'assistant_messages';

    public $timestamps = false;

    protected $fillable = [
        'conversation_id',
        'session_id',
        'role',
        'channel',
        'content',
        'actions_json',
        'metadata_json',
        'memory_processed',
        'created_at',
    ];

    protected $casts = [
        'actions_json'     => 'array',
        'metadata_json'    => 'array',
        'memory_processed' => 'boolean',
        'created_at'       => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AssistantSession::class, 'session_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
    }
}
