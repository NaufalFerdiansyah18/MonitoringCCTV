<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class Camera extends Model
{
    use HasFactory;

    public const CHANNEL_MIN = 1;

    public const CHANNEL_MAX = 16;

    protected $fillable = ['dvr_id', 'channel', 'nama_lokasi', 'kategori'];

    protected function casts(): array
    {
        return [
            'channel' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Camera $camera) {
            if ($camera->channel < self::CHANNEL_MIN || $camera->channel > self::CHANNEL_MAX) {
                throw new InvalidArgumentException(
                    sprintf('Channel harus antara %d dan %d.', self::CHANNEL_MIN, self::CHANNEL_MAX)
                );
            }
        });
    }

    public function dvr(): BelongsTo
    {
        return $this->belongsTo(Dvr::class);
    }
}
