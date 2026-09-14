<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class TechnicalGroup extends Model
{
    use HasFactory;

    protected $fillable = ['nama'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'technical_group_id');
    }

    public function unitCategoryRows(): HasMany
    {
        return $this->hasMany(TechnicalGroupUnitCategory::class);
    }

    public function allowedUnitCategories(): Collection
    {
        return $this->unitCategoryRows->pluck('kategori');
    }
}
