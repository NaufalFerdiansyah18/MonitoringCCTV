<?php

namespace Tests\Feature;

use App\Models\Camera;
use App\Models\Dvr;
use App\Services\RtspGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RtspGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private RtspGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new RtspGenerator;
    }

    public function test_local_mode_uses_ip_local_and_port_local(): void
    {
        $dvr = Dvr::factory()->create([
            'ip_local' => '192.168.1.10',
            'port_local' => 554,
            'ip_public' => null,
            'port_public' => null,
            'username' => 'admin',
            'password' => 'Xtend123',
        ]);

        $url = $this->generator->generateForMode($dvr, 1, 0, 'local');

        $this->assertSame(
            'rtsp://admin:Xtend123@192.168.1.10:554/cam/realmonitor?channel=1&subtype=0',
            $url
        );
    }

    public function test_public_mode_uses_ip_public_and_port_public(): void
    {
        $dvr = Dvr::factory()->create([
            'ip_local' => '192.168.1.10',
            'port_local' => 554,
            'ip_public' => '118.97.163.51',
            'port_public' => 38003,
            'username' => 'admin',
            'password' => 'Xtend123',
        ]);

        $url = $this->generator->generateForMode($dvr, 1, 1, 'public');

        $this->assertSame(
            'rtsp://admin:Xtend123@118.97.163.51:38003/cam/realmonitor?channel=1&subtype=1',
            $url
        );
    }

    public function test_public_mode_returns_null_when_ip_public_empty(): void
    {
        $dvr = Dvr::factory()->create([
            'ip_public' => null,
            'port_public' => null,
        ]);

        $this->assertNull($this->generator->generateForMode($dvr, 1, 0, 'public'));
    }

    public function test_generate_uses_default_mode_and_supports_subtype_override(): void
    {
        config()->set('cctv.mode', 'local');
        config()->set('cctv.subtype', 1);

        $dvr = Dvr::factory()->create([
            'ip_local' => '10.0.0.5',
            'port_local' => 554,
            'username' => 'admin',
            'password' => 'rahasia',
        ]);

        $this->assertSame(
            'rtsp://admin:rahasia@10.0.0.5:554/cam/realmonitor?channel=2&subtype=1',
            $this->generator->generate($dvr, 2)
        );

        $this->assertSame(
            'rtsp://admin:rahasia@10.0.0.5:554/cam/realmonitor?channel=2&subtype=0',
            $this->generator->generate($dvr, 2, 0)
        );
    }

    public function test_special_characters_in_username_and_password_are_encoded(): void
    {
        $dvr = Dvr::factory()->create([
            'ip_local' => '192.168.1.10',
            'port_local' => 554,
            'username' => 'u@ser',
            'password' => 'p:ass&word',
        ]);

        $url = $this->generator->generateForMode($dvr, 3, 0, 'local');

        $this->assertSame(
            'rtsp://u%40ser:p%3Aass%26word@192.168.1.10:554/cam/realmonitor?channel=3&subtype=0',
            $url
        );
    }

    public function test_dvr_and_camera_have_no_rtsp_url_attribute(): void
    {
        $dvr = Dvr::factory()->create();
        $camera = Camera::factory()->for($dvr, 'dvr')->forChannel(1)->create();

        $this->assertArrayNotHasKey('rtsp_url', $dvr->toArray());
        $this->assertArrayNotHasKey('rtsp_url', $camera->toArray());
    }
}
