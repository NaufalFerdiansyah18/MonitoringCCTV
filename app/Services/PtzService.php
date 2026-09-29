<?php

namespace App\Services;

use App\Models\Camera;
use Illuminate\Support\Facades\Http;

class PtzService
{
    public const CODES = [
        'Up',
        'Down',
        'Left',
        'Right',
        'LeftUp',
        'RightUp',
        'LeftDown',
        'RightDown',
        'ZoomIn',
        'ZoomOut',
        'Stop',
    ];

    private mixed $requester = null;

    public function __construct(?callable $requester = null)
    {
        $this->requester = $requester;
    }

    /**
     * Kirim perintah PTZ lewat CGI Dahua. URL CGI tidak pernah dibawa balik ke
     * respons; kredensial dikirim via Basic Auth, bukan di dalam URL.
     *
     * @return array{ok: bool, error?: string}
     */
    public function send(Camera $camera, string $action, string $code): array
    {
        $dvr = $camera->dvr;

        $public = config('cctv.mode') === 'public';
        $host = $public ? $dvr->ip_public : $dvr->ip_local;
        $port = $public ? $dvr->port_public : $dvr->port_local;

        if ($public && blank($host)) {
            return ['ok' => false, 'error' => 'Mode Public tidak tersedia untuk DVR ini (IP public kosong).'];
        }

        $url = sprintf(
            'http://%s:%d/cgi-bin/ptz_control.cgi?action=%s&code=%s&channel=%d',
            $host,
            $port,
            $action,
            $code,
            $camera->channel,
        );

        $requester = $this->requester ?? function (string $url, string $username, string $password): bool {
            return (bool) Http::timeout(3)
                ->withBasicAuth($username, $password)
                ->get($url)
                ->successful();
        };

        try {
            $ok = $requester($url, $dvr->username, $dvr->getPlainPassword());
        } catch (\Throwable) {
            $ok = false;
        }

        if (! $ok) {
            return ['ok' => false, 'error' => 'Permintaan PTZ gagal direspons oleh DVR.'];
        }

        return ['ok' => true];
    }
}
