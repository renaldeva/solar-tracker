<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);

        $site = Site::create([
            'name' => 'Cikarang Industrial Solar Pilot',
            'location' => 'West Java',
            'latitude' => -6.2944,
            'longitude' => 107.1539,
            'timezone' => 'Asia/Jakarta',
            'daily_energy_target_kwh' => 50.00,
        ]);

        Device::create([
            'uid' => 'ST-DEMO-001',
            'name' => 'Dual-Axis Pilot Array 01',
            'site_id' => $site->id,
            'mqtt_username' => 'device_ST-DEMO-001',
            'status' => 'offline',
            'mode' => 'auto',
            'firmware' => '1.0.0',
            'rssi' => -65,
            'settings' => Device::defaultSettings(),
        ]);
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Sign in to your account');
        $response->assertSee('Remember this device');
        $response->assertSee('Contact your administrator');
        $response->assertSee('System Operational');
        $response->assertSee('nama@perusahaan.com');
        $response->assertDontSee('Seed Accounts for Testing');
        $response->assertDontSee('Admin123456!');
        $response->assertDontSee('Operator123456!');
        $response->assertDontSee('Viewer123456!');
    }

    public function test_admin_can_authenticate_and_access_admin_routes(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'Secret123!',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'Secret123!',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect('/dashboard');

        // Admin can access dashboard, device management, and user management
        $this->actingAs($admin)->get('/dashboard')->assertStatus(200);
        $this->actingAs($admin)->get('/device-management')->assertStatus(200);
        $this->actingAs($admin)->get('/user-management')->assertStatus(200);
    }

    public function test_operator_can_authenticate_and_is_forbidden_from_user_management(): void
    {
        $operator = User::factory()->operator()->create([
            'email' => 'operator@example.com',
            'password' => 'Secret123!',
        ]);

        $response = $this->post('/login', [
            'email' => 'operator@example.com',
            'password' => 'Secret123!',
        ]);

        $this->assertAuthenticatedAs($operator);
        $response->assertRedirect('/dashboard');

        // Operator can access dashboard and device management
        $this->actingAs($operator)->get('/dashboard')->assertStatus(200);
        $this->actingAs($operator)->get('/device-management')->assertStatus(200);

        // Operator cannot access user management
        $this->actingAs($operator)->get('/user-management')->assertStatus(403);
    }

    public function test_viewer_can_authenticate_and_is_forbidden_from_management(): void
    {
        $viewer = User::factory()->viewer()->create([
            'email' => 'viewer@example.com',
            'password' => 'Secret123!',
        ]);

        $response = $this->post('/login', [
            'email' => 'viewer@example.com',
            'password' => 'Secret123!',
        ]);

        $this->assertAuthenticatedAs($viewer);
        $response->assertRedirect('/dashboard');

        // Viewer can access dashboard, telemetry, alerts
        $this->actingAs($viewer)->get('/dashboard')->assertStatus(200);
        $this->actingAs($viewer)->get('/telemetry')->assertStatus(200);
        $this->actingAs($viewer)->get('/alerts')->assertStatus(200);

        // Viewer cannot access device management or user management
        $this->actingAs($viewer)->get('/device-management')->assertStatus(403);
        $this->actingAs($viewer)->get('/user-management')->assertStatus(403);
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'RightPassword!',
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'user@example.com',
            'password' => 'WrongPassword',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertSame(
            __('Email atau kata sandi salah.'),
            session('errors')->first('email')
        );
    }

    public function test_users_cannot_authenticate_with_nonexistent_email(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'nobody@example.com',
            'password' => 'AnyPassword123!',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertSame(
            __('Email atau kata sandi salah.'),
            session('errors')->first('email')
        );
    }

    public function test_deactivated_user_cannot_login(): void
    {
        $user = User::factory()->inactive()->create([
            'email' => 'inactive@example.com',
            'password' => 'Secret123!',
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'inactive@example.com',
            'password' => 'Secret123!',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
    }

    public function test_user_password_is_always_hashed_in_database(): void
    {
        $plainPassword = 'MySecretPlaintextPassword123!';

        $user = User::create([
            'name' => 'Hashing Check User',
            'email' => 'hashcheck@example.com',
            'role' => 'operator',
            'password' => $plainPassword,
            'is_active' => true,
        ]);

        // Directly query the raw password from the database
        $rawPassword = (string) User::where('id', $user->id)->value('password');

        // It must NOT be plaintext
        $this->assertNotEquals($plainPassword, $rawPassword);

        // It must begin with standard Bcrypt prefix ($2y$ or $2a$)
        $this->assertMatchesRegularExpression('/^\$2[ayb]\$/', $rawPassword);

        // Hash::check must verify successfully
        $this->assertTrue(Hash::check($plainPassword, $rawPassword));
    }

    public function test_artisan_user_create_command_creates_user_with_hashed_password(): void
    {
        $exitCode = Artisan::call('user:create', [
            '--name' => 'Console Created Admin',
            '--email' => 'cli_admin@example.com',
            '--role' => 'admin',
            '--password' => 'ArtisanSecret123!',
        ]);

        $this->assertSame(0, $exitCode);

        $this->assertDatabaseHas('users', [
            'email' => 'cli_admin@example.com',
            'name' => 'Console Created Admin',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $user = User::where('email', 'cli_admin@example.com')->first();
        $this->assertNotNull($user);

        $rawPassword = (string) User::where('id', $user->id)->value('password');
        $this->assertMatchesRegularExpression('/^\$2[ayb]\$/', $rawPassword);
        $this->assertTrue(Hash::check('ArtisanSecret123!', $rawPassword));
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        $user = User::factory()->create([
            'email' => 'rate_limited@example.com',
            'password' => 'Secret123!',
        ]);

        // Attempt 5 failed logins
        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->post('/login', [
                'email' => 'rate_limited@example.com',
                'password' => 'WrongPassword',
            ]);
        }

        // 6th attempt should be locked out
        $response = $this->from('/login')->post('/login', [
            'email' => 'rate_limited@example.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_unauthenticated_user_cannot_access_user_management(): void
    {
        $response = $this->get('/user-management');

        $response->assertRedirect('/login');
    }
}
