<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    /**
     * Display a listing of system users (Admin only).
     */
    public function index(Request $request): View
    {
        $users = User::orderBy('name')->get();

        $stats = [
            'total' => $users->count(),
            'admins' => $users->filter(fn (User $u) => $u->role === 'admin' || $u->role?->value === 'admin')->count(),
            'operators' => $users->filter(fn (User $u) => $u->role === 'operator' || $u->role?->value === 'operator')->count(),
            'viewers' => $users->filter(fn (User $u) => $u->role === 'viewer' || $u->role?->value === 'viewer')->count(),
            'active' => $users->filter(fn (User $u) => (bool) $u->is_active)->count(),
            'inactive' => $users->filter(fn (User $u) => ! (bool) $u->is_active)->count(),
        ];

        return view('user-management', [
            'users' => $users,
            'stats' => $stats,
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['admin', 'operator', 'viewer'])],
            'password' => ['required', 'string', Password::min(8)],
            'force_password_change' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'force_password_change' => $request->boolean('force_password_change'),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        AuditLog::record(
            $request->user()?->id,
            'user.created',
            User::class,
            (string) $user->id,
            ['email' => $user->email, 'role' => is_object($user->role) ? $user->role->value : $user->role]
        );

        return redirect()->route('user-management')
            ->with('status', __("Akun pengguna {$user->name} ({$user->email}) berhasil dibuat."));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['admin', 'operator', 'viewer'])],
            'password' => ['nullable', 'string', Password::min(8)],
            'force_password_change' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // Safety: Admin cannot deactivate their own account
        if ($request->user()?->id === $user->id && $request->has('is_active') && ! $request->boolean('is_active')) {
            return redirect()->route('user-management')
                ->withErrors(['user' => __('Anda tidak dapat menonaktifkan akun administratif Anda sendiri.')]);
        }

        // Safety: Admin cannot demote their own role
        if ($request->user()?->id === $user->id && $validated['role'] !== 'admin') {
            return redirect()->route('user-management')
                ->withErrors(['user' => __('Anda tidak dapat mengubah peran administratif Anda sendiri.')]);
        }

        $updateData = [
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'role' => $validated['role'],
            'force_password_change' => $request->boolean('force_password_change'),
        ];

        if ($request->has('is_active')) {
            $updateData['is_active'] = $request->boolean('is_active');
        }

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        AuditLog::record(
            $request->user()?->id,
            'user.updated',
            User::class,
            (string) $user->id,
            [
                'email' => $user->email,
                'role' => is_object($user->role) ? $user->role->value : $user->role,
                'is_active' => $user->is_active,
            ]
        );

        return redirect()->route('user-management')
            ->with('status', __("Akun pengguna {$user->name} berhasil diperbarui."));
    }

    /**
     * Toggle active/inactive status of the user.
     */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->id === $user->id) {
            return redirect()->route('user-management')
                ->withErrors(['user' => __('Anda tidak dapat mengubah status akun Anda sendiri.')]);
        }

        $newStatus = ! $user->is_active;
        $user->update(['is_active' => $newStatus]);

        AuditLog::record(
            $request->user()?->id,
            'user.status_toggled',
            User::class,
            (string) $user->id,
            ['email' => $user->email, 'is_active' => $newStatus]
        );

        $statusLabel = $newStatus ? __('diaktifkan') : __('dinonaktifkan');

        return redirect()->route('user-management')
            ->with('status', __("Akun pengguna {$user->name} berhasil {$statusLabel}."));
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->id === $user->id) {
            return redirect()->route('user-management')
                ->withErrors(['user' => __('You cannot delete your own administrative account.')]);
        }

        $email = $user->email;
        $name = $user->name;

        AuditLog::record(
            $request->user()?->id,
            'user.deleted',
            User::class,
            (string) $user->id,
            ['email' => $email, 'name' => $name]
        );

        $user->delete();

        return redirect()->route('user-management')
            ->with('status', __("Akun pengguna {$name} ({$email}) berhasil dihapus."));
    }
}
