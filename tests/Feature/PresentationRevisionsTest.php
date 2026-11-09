<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresentationRevisionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1: formatPowerW helper formats power in Watts only,
     * rounds to whole number when >= 100 W, otherwise 1 decimal,
     * and uses Indonesian separators (. for thousands, , for decimals).
     */
    public function test_power_formatting_helper_formats_watts_only(): void
    {
        // Power >= 100 W -> whole number, dot thousands separator
        $this->assertSame('1.128 W', formatPowerW(1128));
        $this->assertSame('100 W', formatPowerW(100.4));
        $this->assertSame('1.001 W', formatPowerW(1000.6));
        $this->assertSame('15.500 W', formatPowerW(15500));

        // Power < 100 W -> 1 decimal, comma decimal separator
        $this->assertSame('85,3 W', formatPowerW(85.34));
        $this->assertSame('0,0 W', formatPowerW(0));
        $this->assertSame('99,9 W', formatPowerW(99.94));

        // Null power handling
        $this->assertSame('-- W', formatPowerW(null));
        $this->assertSame('--', formatPowerW(null, false));

        // Unit omitted option
        $this->assertSame('1.128', formatPowerW(1128, false));
        $this->assertSame('85,3', formatPowerW(85.34, false));

        // Negative values
        $this->assertSame('-150 W', formatPowerW(-150));
        $this->assertSame('-45,2 W', formatPowerW(-45.2));

        // Never outputs "kW"
        $this->assertStringNotContainsString('kW', formatPowerW(5000));
        $this->assertStringNotContainsString('kw', strtolower(formatPowerW(5000)));
    }

    /**
     * Test 2: Blade scanner test that fails if any user-facing blade template
     * contains hardcoded user-facing English strings outside translation functions.
     */
    public function test_blade_views_contain_no_hardcoded_user_facing_english_strings(): void
    {
        $viewFiles = array_merge(
            glob(resource_path('views/*.blade.php')),
            glob(resource_path('views/auth/*.blade.php'))
        );

        // Disallowed user-facing English phrases that must be translated via __()
        $forbiddenEnglishPhrases = [
            'Instantaneous Power',
            'Daily Energy',
            'Monthly Total',
            'Hourly Production',
            'Wind Speed',
            'Device Monitoring',
            'Energy Monitoring',
            'Historical Data',
            'System Settings',
            'User Management',
            'Device Management',
            'High-Frequency Stream',
            'Standardized Energy Generation Statements',
            'Generate Report',
            'Download CSV Report',
            'Export CSV Dataset',
            'Query Records',
            'Peak DC Power',
            'Average Power',
            'Operating Availability',
            'Triggered Alerts',
            'System Users Directory',
            'Assigned Role',
            'Password Policy',
            'Register New Solar Tracker',
            'Physical Travel Limits',
            'Audit & Compliance',
            'Archival Engine',
            '2-Year Aggregation',
            'High Wind Stow Velocity',
            'Edge-First Safety Architecture Notice',
            'Active Yield',
            'Goal Met',
            'In Progress',
            'Time-Series Waveforms',
            'Recorded Sensor Packets',
            'Must change password',
            'Require password reset',
        ];

        $violations = [];

        foreach ($viewFiles as $file) {
            $baseName = basename($file);
            if ($baseName === 'welcome.blade.php') {
                continue;
            }

            $content = file_get_contents($file);

            // Strip out blade comments {{-- ... --}} and HTML comments <!-- ... -->
            $stripped = preg_replace('/{{--.*?--}}/s', '', $content);
            $stripped = preg_replace('/<!--.*?-->/s', '', $stripped);
            // Strip out script tags <script>...</script>
            $stripped = preg_replace('/<script\b[^>]*>.*?<\/script>/si', '', $stripped);
            // Strip out php blocks @php ... @endphp
            $stripped = preg_replace('/@php.*?@endphp/si', '', $stripped);
            // Strip out translation calls {{ __('...') }} or {{ trans('...') }} or @lang('...')
            $stripped = preg_replace('/__\(\s*[\'"].*?[\'"]\s*\)/s', '', $stripped);
            $stripped = preg_replace('/trans\(\s*[\'"].*?[\'"]\s*\)/s', '', $stripped);
            $stripped = preg_replace('/@lang\(\s*[\'"].*?[\'"]\s*\)/s', '', $stripped);

            foreach ($forbiddenEnglishPhrases as $phrase) {
                if (stripos($stripped, $phrase) !== false) {
                    $violations[] = "{$baseName} contains unlocalized hardcoded phrase: '{$phrase}'";
                }
            }
        }

        $this->assertEmpty($violations, "Hardcoded English phrases found in Blade views:\n".implode("\n", $violations));
    }

    /**
     * Test 3: Indonesian presentation verification when APP_LOCALE=id.
     * Asserts Indonesian glossary terms, Watt (W) only for power, and proper HTML lang.
     */
    public function test_indonesian_locale_presentation_and_power_in_watt_only(): void
    {
        app()->setLocale('id');

        $site = Site::create([
            'name' => 'Cikarang Industrial Pilot',
            'location' => 'Cikarang, West Java',
            'latitude' => -6.2944,
            'longitude' => 107.1539,
            'timezone' => 'Asia/Jakarta',
            'daily_energy_target_kwh' => 50.0,
        ]);

        $device = Device::create([
            'site_id' => $site->id,
            'uid' => 'ST-TEST-001',
            'name' => 'Tracker 01 Test',
            'status' => 'online',
            'mode' => 'auto',
            'settings' => [
                'elev_min' => 10,
                'elev_max' => 85,
                'az_min' => 60,
                'az_max' => 300,
                'wind_stow_ms' => 15.0,
                'measurement_label' => 'Solar array',
                'daily_energy_target_kwh' => 50.0,
            ],
        ]);

        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        // 1. Dashboard View
        $dashboardResponse = $this->actingAs($user)->get(route('dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('<html lang="id"', false);
        $dashboardResponse->assertSee('Dashboard');
        $dashboardResponse->assertSee('Daya Puncak Hari Ini');
        // Ensure "kW" is never shown for power
        $this->assertStringNotContainsString('Daya Puncak Hari Ini (kW)', $dashboardResponse->getContent());

        // 2. Energy Monitoring View
        $energyResponse = $this->actingAs($user)->get(route('energy-monitoring'));
        $energyResponse->assertOk();
        $energyResponse->assertSee('Daya Sesaat');
        $energyResponse->assertSee('Energi Harian');
        $energyResponse->assertSee('Total Bulanan');
        $energyResponse->assertSee('Produksi per Jam');
        // Check power unit is W, not kW
        $this->assertStringNotContainsString('Instantaneous Power (kW)', $energyResponse->getContent());
        $this->assertStringNotContainsString('Daya Sesaat (kW)', $energyResponse->getContent());

        // 3. Device Monitoring View
        $deviceResponse = $this->actingAs($user)->get(route('device-monitoring'));
        $deviceResponse->assertOk();
        $deviceResponse->assertSee('Pemantauan Perangkat');

        // 4. Alerts View
        $alertsResponse = $this->actingAs($user)->get(route('alerts'));
        $alertsResponse->assertOk();
        $alertsResponse->assertSee('Peringatan');

        // 5. Reports View
        $reportsResponse = $this->actingAs($user)->get(route('reports'));
        $reportsResponse->assertOk();
        $reportsResponse->assertSee('Laporan Produksi Energi');
        $reportsResponse->assertSee('Daya Puncak Array');
        $this->assertStringNotContainsString('Peak Array Power (kW)', $reportsResponse->getContent());
    }
}
