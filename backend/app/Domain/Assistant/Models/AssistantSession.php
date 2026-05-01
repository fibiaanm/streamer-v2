<?php

namespace App\Domain\Assistant\Models;

use App\Domain\Assistant\Support\SessionMeta;
use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class AssistantSession extends Model
{
    use HasFactory, HasHashId;

    protected $table = 'assistant_sessions';

    public $timestamps = false;

    protected $fillable = ['conversation_id', 'title', 'started_at', 'last_message_at', 'metadata_json'];

    protected $casts = [
        'started_at'      => 'datetime',
        'last_message_at' => 'datetime',
        'metadata_json'   => 'array',
    ];

    public function incrementMessageCount(): void
    {
        DB::transaction(function () {
            $session = AssistantSession::where('id', $this->id)->lockForUpdate()->first();
            $meta = SessionMeta::fromArray($session->metadata_json);
            $meta->incrementMessageCount();
            $session->metadata_json   = $meta->toArray();
            $session->last_message_at = now();
            $session->save();
        });
    }

    public function addCost(int $input, int $output): void
    {
        DB::transaction(function () use ($input, $output) {
            $session = AssistantSession::where('id', $this->id)->lockForUpdate()->first();
            $meta = SessionMeta::fromArray($session->metadata_json);
            $meta->addCost($input, $output);
            $meta->addResponse();
            $session->metadata_json = $meta->toArray();
            $session->save();
        });
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->last_message_at !== null
            && $this->last_message_at->gt(now()->subHours(24));
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AssistantMessage::class, 'session_id');
    }
}
