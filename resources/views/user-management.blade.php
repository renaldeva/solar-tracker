<x-layouts.app :title="__('Manajemen Pengguna')">
    <!-- Page Header & Action Button -->
    <x-page-header 
        :title="__('User & Role Management')" 
        :subtitle="__('Kelola hak istimewa operator, akses administratif, dan izin hanya-baca. Registrasi publik dinonaktifkan.')"
        :badge="__('Area Keamanan Administrator')"
    >
        <x-slot:actions>
            <div class="flex items-center space-x-2">
                <button 
                    type="button" 
                    onclick="document.getElementById('addUserModal').classList.remove('hidden')"
                    class="inline-flex items-center h-9 px-3 rounded-md bg-accent text-ink text-sm font-medium hover:bg-blue-600 transition-colors cursor-pointer"
                >
                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    <span>{{ __('Tambah Pengguna') }}</span>
                </button>
            </div>
        </x-slot:actions>
    </x-page-header>

    @if(session('status'))
        <div class="mb-5 p-3 rounded-md bg-ok/10 border border-ok/30 text-xs text-ok flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('status') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-ok hover:opacity-75 cursor-pointer">&times;</button>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-5 p-3 rounded-md bg-danger/10 border border-danger/30 text-xs text-danger">
            <div class="font-semibold mb-1">{{ __('Mohon perbaiki kesalahan berikut:') }}</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Role KPI Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <x-kpi-card 
            :title="__('Total Akun')" 
            :value="$stats['total']" 
            unit="akun"
            tone="accent"
            :subtitle="($stats['active'] ?? $stats['total']) . ' aktif • ' . ($stats['inactive'] ?? 0) . ' nonaktif'"
        />

        <x-kpi-card 
            :title="__('Administrator')" 
            :value="$stats['admins']" 
            unit="admin"
            tone="warn"
            :subtitle="__('Akses sistem penuh')"
        />

        <x-kpi-card 
            :title="__('Operator')" 
            :value="$stats['operators']" 
            unit="operator"
            tone="ok"
            :subtitle="__('Kontrol perintah & peringatan')"
        />

        <x-kpi-card 
            :title="__('Pengamat')" 
            :value="$stats['viewers']" 
            unit="viewer"
            tone="neutral"
            :subtitle="__('Pemantauan hanya-baca')"
        />
    </div>

    <!-- Users Table Panel -->
    <div class="bg-panel border border-line rounded-md overflow-hidden mb-6">
        <div class="p-4 border-b border-line flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-ink">{{ __('Direktori Pengguna Sistem') }}</h3>
                <p class="text-xs text-muted mt-0.5">{{ __('Pengaturan peran, status aktif, dan kebijakan kata sandi akun.') }}</p>
            </div>
            <span class="text-xs text-muted font-mono">{{ $stats['total'] }} {{ __('Akun Terdaftar') }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-bg/40 text-muted uppercase text-xs tracking-wider border-b border-line">
                    <tr>
                        <th class="py-2.5 px-4 font-medium">{{ __('Pengguna') }}</th>
                        <th class="py-2.5 px-4 font-medium">{{ __('Alamat Email') }}</th>
                        <th class="py-2.5 px-4 font-medium">{{ __('Peran Ditugaskan') }}</th>
                        <th class="py-2.5 px-4 font-medium">{{ __('Status') }}</th>
                        <th class="py-2.5 px-4 font-medium">{{ __('Kebijakan Kata Sandi') }}</th>
                        <th class="py-2.5 px-4 font-medium">{{ __('Terdaftar') }}</th>
                        <th class="py-2.5 px-4 font-medium text-right">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line font-mono text-xs">
                    @foreach($users as $user)
                        @php
                            $roleString = $user->role instanceof \BackedEnum ? $user->role->value : (string) $user->role;
                            $roleBadge = match($roleString) {
                                'admin' => 'border-warn/30 text-warn bg-warn/10',
                                'operator' => 'border-accent/30 text-accent bg-accent/10',
                                default => 'border-line text-muted bg-bg',
                            };
                            $roleDisplay = match($roleString) {
                                'admin' => __('Admin'),
                                'operator' => __('Operator'),
                                default => __('Pengamat'),
                            };
                            $isActive = (bool) ($user->is_active ?? true);
                        @endphp
                        <tr class="hover:bg-line/20 transition-colors">
                            <td class="py-2.5 px-4 font-sans text-ink font-medium">
                                {{ $user->name }}
                                @if(auth()->id() === $user->id)
                                    <span class="ml-1 text-[11px] text-accent font-sans">({{ __('Anda') }})</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-4 text-muted">{{ $user->email }}</td>
                            <td class="py-2.5 px-4 font-sans">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-medium uppercase border {{ $roleBadge }}">
                                    {{ $roleDisplay }}
                                </span>
                            </td>
                            <td class="py-2.5 px-4">
                                <span class="inline-flex items-center space-x-1.5 {{ $isActive ? 'text-ok' : 'text-danger' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $isActive ? 'bg-ok' : 'bg-danger' }}"></span>
                                    <span>{{ $isActive ? __('Aktif') : __('Nonaktif') }}</span>
                                </span>
                            </td>
                            <td class="py-2.5 px-4 font-sans">
                                @if($user->force_password_change)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-mono border border-warn/30 text-warn bg-warn/10">
                                        {{ __('Wajib ganti kata sandi') }}
                                    </span>
                                @else
                                    <span class="text-muted text-xs font-mono">{{ __('Normal') }}</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-4 text-muted whitespace-nowrap">
                                {{ $user->created_at ? $user->created_at->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') . ' WIB' : '--' }}
                            </td>
                            <td class="py-2.5 px-4 font-sans text-right space-x-1.5 whitespace-nowrap">
                                <button 
                                    type="button" 
                                    onclick="openEditUserModal(@js($user))"
                                    class="inline-flex items-center px-2 py-1 rounded border border-accent/40 text-accent bg-accent/10 hover:bg-accent/20 text-xs font-medium transition-colors"
                                >
                                    {{ __('Edit') }}
                                </button>

                                @if(auth()->id() !== $user->id)
                                    <form method="POST" action="{{ route('user-management.toggle-status', $user) }}" class="inline-block">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center px-2 py-1 rounded border border-line bg-bg hover:bg-line/40 text-ink text-xs font-medium transition-colors cursor-pointer">
                                            {{ $isActive ? __('Nonaktifkan') : __('Aktifkan') }}
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('user-management.destroy', $user) }}" class="inline-block" onsubmit="return confirm('{{ __('Apakah Anda yakin ingin menghapus akun') }} {{ $user->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center px-2 py-1 rounded border border-danger/40 text-danger bg-danger/10 hover:bg-danger/20 text-xs font-medium transition-colors cursor-pointer">
                                            {{ __('Hapus') }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal: Add User -->
    <div id="addUserModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/60 flex items-center justify-center p-4">
        <div class="bg-panel rounded-md max-w-lg w-full border border-line overflow-hidden">
            <div class="px-5 py-4 border-b border-line flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-ink">{{ __('Tambah Pengguna Baru') }}</h3>
                    <p class="text-xs text-muted mt-0.5">{{ __('Buat kredensial akun dan tetapkan peran akses') }}</p>
                </div>
                <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')" class="text-muted hover:text-ink">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('user-management.store') }}" class="p-5 space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-muted uppercase tracking-wider mb-1">{{ __('Nama Lengkap') }} *</label>
                    <input type="text" name="name" required class="w-full text-xs bg-bg border border-line rounded-md px-3 py-2 text-ink focus:outline-none focus:border-accent">
                </div>

                <div>
                    <label class="block font-semibold text-muted uppercase tracking-wider mb-1">{{ __('Alamat Email') }} *</label>
                    <input type="email" name="email" required class="w-full text-xs bg-bg border border-line rounded-md px-3 py-2 text-ink focus:outline-none focus:border-accent">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1">{{ __('Peran Sistem') }} *</label>
                        <select name="role" required class="w-full text-xs bg-bg border border-line rounded-md px-3 py-2 text-ink focus:outline-none focus:border-accent">
                            <option value="viewer">{{ __('Pengamat (Hanya-Baca)') }}</option>
                            <option value="operator">{{ __('Operator (Kontrol Perintah)') }}</option>
                            <option value="admin">{{ __('Administrator (Penuh)') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1">{{ __('Kata Sandi Sementara') }} *</label>
                        <input type="password" name="password" required class="w-full text-xs bg-bg border border-line rounded-md px-3 py-2 text-ink focus:outline-none focus:border-accent">
                    </div>
                </div>

                <div class="pt-2">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="force_password_change" value="1" checked class="w-4 h-4 rounded border-line bg-bg text-accent focus:ring-0 cursor-pointer">
                        <span class="ml-2 text-xs text-muted">{{ __('Wajibkan ganti kata sandi pada login pertama') }}</span>
                    </label>
                </div>

                <div class="pt-3 border-t border-line flex items-center justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')" class="px-3 py-1.5 rounded-md border border-line text-muted hover:text-ink text-xs font-medium transition-colors">
                        {{ __('Batal') }}
                    </button>
                    <button type="submit" class="px-3 py-1.5 rounded-md bg-accent hover:bg-blue-600 text-ink text-xs font-medium transition-colors cursor-pointer">
                        {{ __('Simpan Pengguna') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit User -->
    <div id="editUserModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/60 flex items-center justify-center p-4">
        <div class="bg-panel rounded-md max-w-lg w-full border border-line overflow-hidden">
            <div class="px-5 py-4 border-b border-line flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-ink">{{ __('Edit Pengguna') }}</h3>
                    <p class="text-xs text-muted mt-0.5">{{ __('Perbarui informasi akun dan peran pengguna') }}</p>
                </div>
                <button type="button" onclick="document.getElementById('editUserModal').classList.add('hidden')" class="text-muted hover:text-ink">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="editUserForm" method="POST" action="" class="p-5 space-y-4 text-xs">
                @csrf
                @method('PUT')
                <div>
                    <label class="block font-semibold text-muted uppercase tracking-wider mb-1">{{ __('Nama Lengkap') }} *</label>
                    <input type="text" id="editName" name="name" required class="w-full text-xs bg-bg border border-line rounded-md px-3 py-2 text-ink focus:outline-none focus:border-accent">
                </div>

                <div>
                    <label class="block font-semibold text-muted uppercase tracking-wider mb-1">{{ __('Alamat Email') }} *</label>
                    <input type="email" id="editEmail" name="email" required class="w-full text-xs bg-bg border border-line rounded-md px-3 py-2 text-ink focus:outline-none focus:border-accent">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1">{{ __('Peran Sistem') }} *</label>
                        <select id="editRole" name="role" required class="w-full text-xs bg-bg border border-line rounded-md px-3 py-2 text-ink focus:outline-none focus:border-accent">
                            <option value="viewer">{{ __('Pengamat (Hanya-Baca)') }}</option>
                            <option value="operator">{{ __('Operator (Kontrol Perintah)') }}</option>
                            <option value="admin">{{ __('Administrator (Penuh)') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1">{{ __('Kata Sandi Baru (Opsional)') }}</label>
                        <input type="password" name="password" placeholder="{{ __('Biarkan kosong jika tidak diubah') }}" class="w-full text-xs bg-bg border border-line rounded-md px-3 py-2 text-ink placeholder-muted focus:outline-none focus:border-accent">
                    </div>
                </div>

                <div class="pt-2">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" id="editForcePassword" name="force_password_change" value="1" class="w-4 h-4 rounded border-line bg-bg text-accent focus:ring-0 cursor-pointer">
                        <span class="ml-2 text-xs text-muted">{{ __('Wajibkan ganti kata sandi pada login berikutnya') }}</span>
                    </label>
                </div>

                <div class="pt-3 border-t border-line flex items-center justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('editUserModal').classList.add('hidden')" class="px-3 py-1.5 rounded-md border border-line text-muted hover:text-ink text-xs font-medium transition-colors">
                        {{ __('Batal') }}
                    </button>
                    <button type="submit" class="px-3 py-1.5 rounded-md bg-accent hover:bg-blue-600 text-ink text-xs font-medium transition-colors cursor-pointer">
                        {{ __('Perbarui Pengguna') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function openEditUserModal(user) {
            document.getElementById('editUserForm').action = `/user-management/${user.id}`;
            document.getElementById('editName').value = user.name || '';
            document.getElementById('editEmail').value = user.email || '';
            const role = typeof user.role === 'object' && user.role !== null ? user.role.value : user.role;
            document.getElementById('editRole').value = role || 'viewer';
            document.getElementById('editForcePassword').checked = Boolean(user.force_password_change);
            document.getElementById('editUserModal').classList.remove('hidden');
        }
    </script>
    @endpush
</x-layouts.app>
