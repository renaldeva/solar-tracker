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
                    class="inline-flex items-center h-10 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition-colors shadow-xs cursor-pointer"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    <span>{{ __('Tambah Pengguna') }}</span>
                </button>
            </div>
        </x-slot:actions>
    </x-page-header>

    @if(session('status'))
        <div class="mb-5 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2.5">
                <svg class="w-4 h-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="font-medium">{{ session('status') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-700 hover:opacity-75 cursor-pointer text-lg leading-none">&times;</button>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-5 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-rose-800 shadow-xs">
            <div class="font-semibold mb-1">{{ __('Mohon perbaiki kesalahan berikut:') }}</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Status Banner: "Apakah semuanya baik-baik saja?" -->
    <div class="mb-6 p-4 rounded-2xl bg-white border border-line shadow-xs flex items-center justify-between">
        <div class="flex items-center space-x-3.5">
            <div class="w-3.5 h-3.5 rounded-full bg-emerald-500 ring-4 ring-emerald-50"></div>
            <div>
                <h2 class="text-sm font-semibold text-ink">
                    Pengendalian Akses Pengguna Terproteksi
                </h2>
                <p class="text-xs text-muted mt-0.5">
                    Total <strong class="text-ink">{{ $stats['total'] }} akun</strong> terdaftar ({{ $stats['admins'] }} Admin, {{ $stats['operators'] }} Operator, {{ $stats['viewers'] }} Pengamat).
                </p>
            </div>
        </div>
        <div class="hidden sm:flex items-center space-x-2">
            <span class="px-3 py-1 rounded-xl text-xs font-mono font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                RBAC ACTIVE
            </span>
        </div>
    </div>

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

    <!-- Users Table Card (Smart Nursery Style) -->
    <div class="bg-white border border-line rounded-2xl overflow-hidden mb-6 shadow-xs">
        <div class="p-4 sm:p-5 border-b border-line flex items-center justify-between bg-slate-50/40">
            <div>
                <h3 class="text-sm font-semibold text-ink">{{ __('Direktori Pengguna Sistem') }}</h3>
                <p class="text-xs text-muted mt-0.5">{{ __('Pengaturan peran, status aktif, dan kebijakan kata sandi akun.') }}</p>
            </div>
            <span class="text-xs text-muted font-mono font-medium">{{ $stats['total'] }} {{ __('Akun Terdaftar') }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50/70 text-muted uppercase text-[11px] font-semibold tracking-wider border-b border-line">
                    <tr>
                        <th class="py-3 px-4">{{ __('Pengguna') }}</th>
                        <th class="py-3 px-4">{{ __('Alamat Email') }}</th>
                        <th class="py-3 px-4">{{ __('Peran Ditugaskan') }}</th>
                        <th class="py-3 px-4">{{ __('Status') }}</th>
                        <th class="py-3 px-4">{{ __('Kebijakan Kata Sandi') }}</th>
                        <th class="py-3 px-4">{{ __('Terdaftar') }}</th>
                        <th class="py-3 px-4 text-right">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line font-mono text-xs">
                    @foreach($users as $user)
                        @php
                            $roleString = $user->role instanceof \BackedEnum ? $user->role->value : (string) $user->role;
                            $roleBadge = match($roleString) {
                                'admin' => 'border-amber-200 text-amber-700 bg-amber-50 font-semibold',
                                'operator' => 'border-blue-200 text-blue-700 bg-blue-50 font-semibold',
                                default => 'border-line text-muted bg-slate-50 font-semibold',
                            };
                            $roleDisplay = match($roleString) {
                                'admin' => __('Admin'),
                                'operator' => __('Operator'),
                                default => __('Pengamat'),
                            };
                            $isActive = (bool) ($user->is_active ?? true);
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3 px-4 font-sans text-ink font-semibold">
                                {{ $user->name }}
                                @if(auth()->id() === $user->id)
                                    <span class="ml-1 text-[11px] text-blue-600 font-sans font-bold">({{ __('Anda') }})</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-muted">{{ $user->email }}</td>
                            <td class="py-3 px-4 font-sans">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-mono uppercase border {{ $roleBadge }}">
                                    {{ $roleDisplay }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center space-x-1.5 font-semibold {{ $isActive ? 'text-emerald-600' : 'text-rose-600' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $isActive ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                    <span>{{ $isActive ? __('Aktif') : __('Nonaktif') }}</span>
                                </span>
                            </td>
                            <td class="py-3 px-4 font-sans">
                                @if($user->force_password_change)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-mono border border-amber-200 text-amber-700 bg-amber-50 font-semibold">
                                        {{ __('Wajib ganti kata sandi') }}
                                    </span>
                                @else
                                    <span class="text-muted text-xs font-mono font-medium">{{ __('Normal') }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-muted whitespace-nowrap">
                                {{ $user->created_at ? $user->created_at->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') . ' WIB' : '--' }}
                            </td>
                            <td class="py-3 px-4 font-sans text-right space-x-1.5 whitespace-nowrap">
                                <button 
                                    type="button" 
                                    onclick="openEditUserModal(@js($user))"
                                    class="inline-flex items-center px-2.5 py-1 rounded-lg border border-blue-200 text-blue-700 bg-blue-50 hover:bg-blue-100 text-xs font-semibold transition-colors shadow-2xs cursor-pointer"
                                >
                                    {{ __('Edit') }}
                                </button>

                                @if(auth()->id() !== $user->id)
                                    <form method="POST" action="{{ route('user-management.toggle-status', $user) }}" class="inline-block">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center px-2.5 py-1 rounded-lg border border-line bg-white hover:bg-slate-50 text-ink text-xs font-semibold transition-colors shadow-2xs cursor-pointer">
                                            {{ $isActive ? __('Nonaktifkan') : __('Aktifkan') }}
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('user-management.destroy', $user) }}" class="inline-block" onsubmit="return confirm('{{ __('Apakah Anda yakin ingin menghapus akun') }} {{ $user->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center px-2.5 py-1 rounded-lg border border-rose-200 text-rose-700 bg-rose-50 hover:bg-rose-100 text-xs font-semibold transition-colors cursor-pointer shadow-2xs">
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
    <div id="addUserModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full border border-line overflow-hidden shadow-xl">
            <div class="px-6 py-4.5 border-b border-line flex items-center justify-between bg-slate-50/50">
                <div>
                    <h3 class="text-sm font-bold text-ink">{{ __('Tambah Pengguna Baru') }}</h3>
                    <p class="text-xs text-muted mt-0.5">{{ __('Buat kredensial akun dan tetapkan peran akses') }}</p>
                </div>
                <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')" class="text-muted hover:text-ink cursor-pointer">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('user-management.store') }}" class="p-6 space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Nama Lengkap') }} *</label>
                    <input type="text" name="name" required class="w-full text-xs bg-white border border-line rounded-xl px-3.5 py-2.5 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                </div>

                <div>
                    <label class="block font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Alamat Email') }} *</label>
                    <input type="email" name="email" required class="w-full text-xs bg-white border border-line rounded-xl px-3.5 py-2.5 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Peran Sistem') }} *</label>
                        <div class="relative">
                            <select name="role" required class="appearance-none w-full text-xs bg-white border border-line rounded-xl pl-3 pr-8 py-2.5 text-ink focus:outline-none focus:border-blue-500 shadow-2xs font-semibold">
                                <option value="viewer">{{ __('Pengamat (Hanya-Baca)') }}</option>
                                <option value="operator">{{ __('Operator (Kontrol Perintah)') }}</option>
                                <option value="admin">{{ __('Administrator (Penuh)') }}</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-muted">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Kata Sandi Sementara') }} *</label>
                        <input type="password" name="password" required class="w-full text-xs bg-white border border-line rounded-xl px-3.5 py-2.5 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                    </div>
                </div>

                <div class="pt-2">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="force_password_change" value="1" checked class="w-4 h-4 rounded border-line text-blue-600 focus:ring-0 cursor-pointer">
                        <span class="ml-2.5 text-xs text-muted font-medium">{{ __('Wajibkan ganti kata sandi pada login pertama') }}</span>
                    </label>
                </div>

                <div class="pt-4 border-t border-line flex items-center justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-line text-muted hover:text-ink text-xs font-medium transition-colors bg-white cursor-pointer">
                        {{ __('Batal') }}
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition-colors shadow-xs cursor-pointer">
                        {{ __('Simpan Pengguna') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit User -->
    <div id="editUserModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full border border-line overflow-hidden shadow-xl">
            <div class="px-6 py-4.5 border-b border-line flex items-center justify-between bg-slate-50/50">
                <div>
                    <h3 class="text-sm font-bold text-ink">{{ __('Edit Pengguna') }}</h3>
                    <p class="text-xs text-muted mt-0.5">{{ __('Perbarui informasi akun dan peran pengguna') }}</p>
                </div>
                <button type="button" onclick="document.getElementById('editUserModal').classList.add('hidden')" class="text-muted hover:text-ink cursor-pointer">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="editUserForm" method="POST" action="" class="p-6 space-y-4 text-xs">
                @csrf
                @method('PUT')
                <div>
                    <label class="block font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Nama Lengkap') }} *</label>
                    <input type="text" id="editName" name="name" required class="w-full text-xs bg-white border border-line rounded-xl px-3.5 py-2.5 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                </div>

                <div>
                    <label class="block font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Alamat Email') }} *</label>
                    <input type="email" id="editEmail" name="email" required class="w-full text-xs bg-white border border-line rounded-xl px-3.5 py-2.5 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Peran Sistem') }} *</label>
                        <div class="relative">
                            <select id="editRole" name="role" required class="appearance-none w-full text-xs bg-white border border-line rounded-xl pl-3 pr-8 py-2.5 text-ink focus:outline-none focus:border-blue-500 shadow-2xs font-semibold">
                                <option value="viewer">{{ __('Pengamat (Hanya-Baca)') }}</option>
                                <option value="operator">{{ __('Operator (Kontrol Perintah)') }}</option>
                                <option value="admin">{{ __('Administrator (Penuh)') }}</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-muted">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Kata Sandi Baru (Opsional)') }}</label>
                        <input type="password" name="password" placeholder="{{ __('Biarkan kosong jika tidak diubah') }}" class="w-full text-xs bg-white border border-line rounded-xl px-3.5 py-2.5 text-ink placeholder-muted focus:outline-none focus:border-blue-500 shadow-2xs">
                    </div>
                </div>

                <div class="pt-2">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" id="editForcePassword" name="force_password_change" value="1" class="w-4 h-4 rounded border-line text-blue-600 focus:ring-0 cursor-pointer">
                        <span class="ml-2.5 text-xs text-muted font-medium">{{ __('Wajibkan ganti kata sandi pada login berikutnya') }}</span>
                    </label>
                </div>

                <div class="pt-4 border-t border-line flex items-center justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('editUserModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-line text-muted hover:text-ink text-xs font-medium transition-colors bg-white cursor-pointer">
                        {{ __('Batal') }}
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition-colors shadow-xs cursor-pointer">
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
