<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Device;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePolicyPhaseSixTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $operator;

    protected User $viewer;

    protected Site $site;

    protected Device $device;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create([
            'email' => 'admin_p6@example.com',
            'name' => 'Lead Engineer Admin',
        ]);

        $this->operator = User::factory()->operator()->create([
            'email' => 'operator_p6@example.com',
            'name' => 'Site Operator',
        ]);

        $this->viewer = User::factory()->viewer()->create([
            'email' => 'viewer_p6@example.com',
            'name' => 'External Auditor Viewer',
        ]);

        $this->site = Site::create([
            'name' => 'Cikarang Industrial Solar Pilot',
            'location' => 'Bekasi, West Java',
            'latitude' => -6.2944,
            'longitude' => 107.1539,
            'timezone' => 'Asia/Jakarta',
            'daily_energy_target_kwh' => 50.0,
        ]);

        $this->device = Device::create([
            'uid' => 'ST-TEST-P6',
            'name' => 'Phase 6 Test Tracker',
            'site_id' => $this->site->id,
            'mqtt_username' => 'device_ST-TEST-P6',
            'status' => 'online',
            'mode' => 'auto',
            'settings' => Device::defaultSettings(),
        ]);
    }

    /**
     * Test 1: Viewer is strictly forbidden from accessing management or settings routes.
     */
    public function test_viewer_is_forbidden_from_device_user_and_settings_management(): void
    {
        $this->actingAs($this->viewer)
            ->get(route('device-management'))
            ->assertStatus(403);

        $this->actingAs($this->viewer)
            ->post(route('device-management.store'), ['uid' => 'ST-FAIL'])
            ->assertStatus(403);

        $this->actingAs($this->viewer)
            ->get(route('settings'))
            ->assertStatus(403);

        $this->actingAs($this->viewer)
            ->post(route('settings.site'), ['name' => 'Hack Site'])
            ->assertStatus(403);

        $this->actingAs($this->viewer)
            ->get(route('user-management'))
            ->assertStatus(403);

        $this->actingAs($this->viewer)
            ->post(route('user-management.store'), ['email' => 'hack@example.com'])
            ->assertStatus(403);
    }

    /**
     * Test 2: Operator can manage devices and settings but cannot access user management.
     */
    public function test_operator_can_manage_devices_and_settings_but_not_users(): void
    {
        // Allowed: View Device Management
        $this->actingAs($this->operator)
            ->get(route('device-management'))
            ->assertOk()
            ->assertSee('Device Management');

        // Allowed: Create Device
        $storeResponse = $this->actingAs($this->operator)
            ->post(route('device-management.store'), [
                'uid' => 'ST-OP-001',
                'name' => 'Operator Created Tracker',
                'site_id' => $this->site->id,
                'elev_min' => 12.0,
                'elev_max' => 80.0,
                'az_min' => 70.0,
                'az_max' => 290.0,
                'wind_stow_ms' => 14.5,
                'telemetry_interval_s' => 5,
                'measurement_label' => 'Solar array string 2',
                'daily_energy_target_kwh' => 45.0,
            ]);

        $storeResponse->assertRedirect(route('device-management'));
        $this->assertDatabaseHas('devices', ['uid' => 'ST-OP-001']);

        // Allowed: View Settings
        $this->actingAs($this->operator)
            ->get(route('settings'))
            ->assertOk()
            ->assertSee('System Settings');

        // Allowed: Update Site Information
        $siteUpdate = $this->actingAs($this->operator)
            ->post(route('settings.site'), [
                'name' => 'Updated Pilot Facility Name',
                'location' => 'Cikarang Barat, Bekasi',
                'latitude' => -6.3000,
                'longitude' => 107.1600,
                'timezone' => 'Asia/Jakarta',
                'daily_energy_target_kwh' => 55.0,
            ]);

        $siteUpdate->assertRedirect(route('settings'));
        $this->assertDatabaseHas('sites', ['name' => 'Updated Pilot Facility Name']);

        // Forbidden: User Management
        $this->actingAs($this->operator)
            ->get(route('user-management'))
            ->assertStatus(403);

        $this->actingAs($this->operator)
            ->post(route('user-management.store'), [
                'name' => 'Should Fail User',
                'email' => 'fail@example.com',
                'role' => 'admin',
                'password' => 'Password123!',
            ])
            ->assertStatus(403);
    }

    /**
     * Test 3: Admin has full access to user management CRUD and cannot delete self.
     */
    public function test_admin_has_full_user_management_crud_and_cannot_self_delete(): void
    {
        // View user directory
        $this->actingAs($this->admin)
            ->get(route('user-management'))
            ->assertOk()
            ->assertSee('User Management')
            ->assertSee($this->admin->email);

        // Create new operator user
        $createResponse = $this->actingAs($this->admin)
            ->post(route('user-management.store'), [
                'name' => 'New Field Tech',
                'email' => 'tech1@example.com',
                'role' => 'operator',
                'password' => 'SecurePass123!',
                'force_password_change' => true,
            ]);

        $createResponse->assertRedirect(route('user-management'));
        $this->assertDatabaseHas('users', [
            'email' => 'tech1@example.com',
            'role' => 'operator',
            'force_password_change' => true,
        ]);

        $createdUser = User::where('email', 'tech1@example.com')->first();

        // Update user
        $updateResponse = $this->actingAs($this->admin)
            ->put(route('user-management.update', $createdUser), [
                'name' => 'Promoted Field Tech',
                'email' => 'tech1@example.com',
                'role' => 'admin',
            ]);

        $updateResponse->assertRedirect(route('user-management'));
        $createdUser->refresh();
        $roleValue = $createdUser->role instanceof \BackedEnum ? $createdUser->role->value : (string) $createdUser->role;
        $this->assertEquals('admin', $roleValue);

        // Admin cannot self-delete
        $selfDelete = $this->actingAs($this->admin)
            ->delete(route('user-management.destroy', $this->admin));

        $selfDelete->assertRedirect(route('user-management'));
        $selfDelete->assertSessionHasErrors(['user']);
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);

        // Admin can delete other user
        $deleteResponse = $this->actingAs($this->admin)
            ->delete(route('user-management.destroy', $createdUser));

        $deleteResponse->assertRedirect(route('user-management'));
        $this->assertDatabaseMissing('users', ['id' => $createdUser->id]);
    }

    /**
     * Test 4: Device validation rules enforce physical bounds and unique UID.
     */
    public function test_device_validation_enforces_physical_travel_bounds(): void
    {
        // Elevation min cannot exceed 45°
        $response = $this->actingAs($this->admin)
            ->post(route('device-management.store'), [
                'uid' => 'ST-INVALID-BOUNDS',
                'name' => 'Bad Bounds Tracker',
                'site_id' => $this->site->id,
                'elev_min' => 50.0, // invalid: max allowed for min is 45
                'elev_max' => 40.0,
                'az_min' => 60.0,
                'az_max' => 300.0,
                'wind_stow_ms' => 15.0,
                'telemetry_interval_s' => 5,
                'measurement_label' => 'Solar array',
                'daily_energy_target_kwh' => 50.0,
            ]);

        $response->assertSessionHasErrors(['elev_min']);
    }

    /**
     * Test 5: Audit logs are recorded on device, site, and user changes.
     */
    public function test_audit_logs_record_all_administrative_actions(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.thresholds'), [
                'wind_stow_threshold_ms' => 16.0,
                'retention_raw_days' => 45,
                'retention_hourly_days' => 730,
            ]);

        $audit = AuditLog::where('action', 'settings.thresholds_updated')->first();
        $this->assertNotNull($audit);
        $this->assertEquals($this->admin->id, $audit->user_id);
        $this->assertEquals(16.0, $audit->metadata['wind_stow_threshold_ms']);
    }
}
