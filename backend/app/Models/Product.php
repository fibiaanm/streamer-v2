<?php

namespace App\Models;

use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, HasHashId;

    protected $fillable = ['name', 'slug', 'description'];

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class);
    }

    public function enterpriseProducts(): HasMany
    {
        return $this->hasMany(EnterpriseProduct::class);
    }
}
