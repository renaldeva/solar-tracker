<?php

namespace Tests\Unit;

use App\Models\Alert;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

class AlertMessageTest extends TestCase
{
    /**
     * Test alert message rendering for DEVICE_OFFLINE with metadata.
     */
    public function test_alert_render_message_device_offline(): void
    {
        $alert = new Alert([
            'code' => 'DEVICE_OFFLINE',
            'message' => 'Device ST-DEMO-001 connection lost (broker LWT or timeout)',
            'metadata' => [
                'status' => 'offline',
                'reason' => 'lwt_connection_lost',
            ],
        ]);

        $rendered = $alert->renderMessage();

        $this->assertIsString($rendered);
        $this->assertNotEmpty($rendered);
        $this->assertEquals($rendered, $alert->display_message);
    }

    /**
     * Test alert message rendering for COMM_LOSS with metadata.
     */
    public function test_alert_render_message_comm_loss(): void
    {
        $alert = new Alert([
            'code' => 'COMM_LOSS',
            'message' => 'Device ST-DEMO-001 connection lost (broker LWT or timeout)',
            'metadata' => [
                'status' => 'offline',
                'reason' => 'lwt_connection_lost',
            ],
        ]);

        $rendered = $alert->renderMessage();

        $this->assertIsString($rendered);
        $this->assertNotEmpty($rendered);
        $this->assertEquals($rendered, $alert->display_message);
    }

    /**
     * Test alert message rendering with nested array metadata does not trigger mb_ucfirst TypeError.
     */
    public function test_alert_render_message_handles_nested_array_metadata(): void
    {
        $alert = new Alert([
            'code' => 'DEVICE_OFFLINE',
            'message' => 'Device ST-DEMO-001 connection lost (broker LWT or timeout)',
            'metadata' => [
                'status' => 'offline',
                'reason' => 'lwt_connection_lost',
                'nested' => ['foo' => 'bar', 'sub' => ['deep' => true]],
                'tags' => ['sensor', 'connectivity'],
            ],
        ]);

        $rendered = $alert->renderMessage();

        $this->assertIsString($rendered);
        $this->assertNotEmpty($rendered);
        $this->assertEquals($rendered, $alert->display_message);
    }

    /**
     * Test alert message rendering with null or non-array metadata.
     */
    public function test_alert_render_message_handles_null_or_empty_metadata(): void
    {
        $alert = new Alert([
            'code' => 'COMM_LOSS',
            'message' => 'Communication link lost',
            'metadata' => null,
        ]);

        $rendered = $alert->renderMessage();

        $this->assertIsString($rendered);
        $this->assertNotEmpty($rendered);
    }

    /**
     * Test alert message falls back to message when code has no translation.
     */
    public function test_alert_render_message_fallback_for_unknown_code(): void
    {
        $alert = new Alert([
            'code' => 'CUSTOM_UNKNOWN_ALERT_CODE_123',
            'message' => 'Custom alert message occurred',
            'metadata' => ['raw' => ['level' => 'high']],
        ]);

        $rendered = $alert->renderMessage();

        $this->assertIsString($rendered);
        $this->assertEquals('Custom alert message occurred', $rendered);
    }

    /**
     * Test that if Lang::get returns an array (nested translation key), renderMessage falls back safely.
     */
    public function test_alert_render_message_handles_non_string_translation(): void
    {
        // 'alerts' key itself returns an array of all alerts
        $alert = new Alert([
            'code' => '', // $codeKey will be 'alerts.', which returns array
            'message' => 'Fallback message text',
            'metadata' => ['status' => 'offline'],
        ]);

        $rendered = $alert->renderMessage();

        $this->assertIsString($rendered);
        $this->assertEquals('Fallback message text', $rendered);
    }
}
