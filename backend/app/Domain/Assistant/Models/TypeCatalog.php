<?php

namespace App\Domain\Assistant\Models;

use App\Models\User;
use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TypeCatalog extends Model
{
    use HasFactory, HasHashId;

    protected $table = 'type_catalogs';

    public $timestamps = false;

    protected $fillable = ['user_id', 'domain', 'name'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
