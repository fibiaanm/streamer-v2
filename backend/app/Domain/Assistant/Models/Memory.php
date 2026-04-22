<?php

namespace App\Domain\Assistant\Models;

use App\Models\User;
use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Memory extends Model
{
    use HasFactory, HasHashId;

    protected $table = 'memories';

    protected $fillable = ['user_id', 'category', 'description', 'content'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
