<?php

namespace Tests\Unit;

use App\Models\Alert;
use Tests\TestCase;

class AlertMessageTest extends TestCase
{
    /**
     * Test alert message rendering with nested array metadata does not trigger mb_ucfirst TypeError.
     */
    public function test_alert_render_message_handles_nested_array_metadata(): void
    {
        $alert = new Alert([
            'code' => 'HIGH_WIND_STOW',
            'message' => 'High wind safety stow active',
            'metadata' => [
                'details' => ['nested' => 'array_value'],
                'flags' => ['wind_alarm' => false, 'limit_elev' => true],
                'speed' => 18.5,
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
}
