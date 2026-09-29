<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicalGroupUnitCategory extends Model
{
    use HasFactory;

    protected $fillable = ['technical_group_id', 'kategori'];

    protected function casts(): array
    {
        return [
            'kategori' => 'string',
        ];
    }

    public function technicalGroup(): BelongsTo
    {
        return $this->belongsTo(TechnicalGroup::class);
    }
}
