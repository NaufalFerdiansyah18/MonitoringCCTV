<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Dvr extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'nama',
        'ip_local',
        'port_local',
        'ip_public',
        'port_public',
        'username',
        'password',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'port_local' => 'integer',
            'port_public' => 'integer',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function cameras(): HasMany
    {
        return $this->hasMany(Camera::class);
    }

    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => Crypt::encryptString($value),
        );
    }

    public function getPlainPassword(): string
    {
        return Crypt::decryptString($this->getAttributes()['password']);
    }
}
