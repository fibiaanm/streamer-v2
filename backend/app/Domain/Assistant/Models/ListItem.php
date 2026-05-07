<?php

namespace App\Domain\Assistant\Models;

use App\Models\User;
use App\Traits\HasHashId;
use Database\Factories\Domain\Assistant\Models\ListItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListItem extends Model
{
    use HasFactory, HasHashId;

    protected static function newFactory(): ListItemFactory
    {
        return ListItemFactory::new();
    }

    protected $table = 'list_items';

    protected $fillable = ['list_id', 'added_by_user_id', 'content', 'status', 'position'];

    public function list(): BelongsTo
    {
        return $this->belongsTo(AssistantList::class, 'list_id');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by_user_id');
    }
}
