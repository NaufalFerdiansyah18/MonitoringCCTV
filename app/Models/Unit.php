<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory;

    public const KATEGORI = ['kebun', 'pks', 'ro'];

    protected $fillable = ['kode', 'nama', 'kategori'];

    protected function casts(): array
    {
        return [
            'kategori' => 'string',
        ];
    }

    public function dvrs(): HasMany
    {
        return $this->hasMany(Dvr::class);
    }
}
