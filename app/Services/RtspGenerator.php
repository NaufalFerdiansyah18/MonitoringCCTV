<?php

namespace App\Services;

use App\Models\Dvr;

class RtspGenerator
{
    public function generate(Dvr $dvr, int $channel, ?int $subtype = null): string
    {
        $subtype ??= config('cctv.subtype');

        return $this->generateForMode(
            $dvr,
            $channel,
            $subtype,
            config('cctv.mode'),
        );
    }

    public function generateForMode(Dvr $dvr, int $channel, int $subtype, string $mode): ?string
    {
        $host = $mode === 'public' ? $dvr->ip_public : $dvr->ip_local;
        $port = $mode === 'public' ? $dvr->port_public : $dvr->port_local;

        if ($mode === 'public' && blank($host)) {
            return null;
        }

        $username = rawurlencode($dvr->username);
        $password = rawurlencode($dvr->getPlainPassword());

        return sprintf(
            'rtsp://%s:%s@%s:%d/cam/realmonitor?channel=%d&subtype=%d',
            $username,
            $password,
            $host,
            $port,
            $channel,
            $subtype,
        );
    }
}
