<?php

namespace App\Domain\Assistant\Models;

use App\Domain\Assistant\Support\HasEventReference;
use App\Domain\Assistant\Support\NotifiesOrphanedEventReferences;
use App\Models\User;
use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssistantList extends Model
{
    use HasFactory, HasHashId, SoftDeletes, HasEventReference, NotifiesOrphanedEventReferences;

    protected $table = 'assistant_lists';

    protected $fillable = ['user_id', 'name', 'type'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ListItem::class, 'list_id')->orderBy('position');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(ListShare::class, 'list_id');
    }

    public function eventReferenceLabel(): string
    {
        $pending = $this->items()->where('status', 'pending')->count();
        return "lista '{$this->name}' ({$pending} ítems pendientes)";
    }
}
