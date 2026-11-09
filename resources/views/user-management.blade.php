<x-layouts.app :title="__('Manajemen Pengguna')">
    <!-- Page Header & Action Button -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold uppercase tracking-wider bg-purple-50 text-purple-700 border border-purple-200">
                    {{ __('Area Keamanan Administrator') }}
                </span>
                <span class="text-sm font-medium text-slate-400">{{ __('Kontrol Akses Berbasis Peran') }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 mt-1">{{ __('User & Role Management') }}</h1>
            <p class="text-sm text-slate-500 mt-1">{{ __('Kelola hak istimewa operator, akses administratif, dan izin hanya-baca. Registrasi publik dinonaktifkan.') }}</p>
        </div>

        <div class="flex items-center space-x-2">
            <button 
                type="button" 
                onclick="document.getElementById('addUserModal').classList.remove('hidden')"
                class="inline-flex items-center px-4 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-xs transition-colors h-11 min-h-[44px]"
            >
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                {{ __('Tambah Pengguna') }}
            </button>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-sm font-medium text-emerald-800 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('status') }}</span>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-sm font-medium text-red-800">
            <div class="font-bold mb-1">{{ __('Mohon perbaiki kesalahan berikut:') }}</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Role KPI Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-2xs">
            <span class="text-xs sm:text-[13px] font-medium text-slate-500">{{ __('Total Akun') }}</span>
            <div class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 font-mono">{{ $stats['total'] }}</div>
            <div class="mt-1 text-xs text-slate-400">{{ $stats['active'] ?? $stats['total'] }} {{ __('aktif') }} &bull; {{ $stats['inactive'] ?? 0 }} {{ __('nonaktif') }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-2xs">
            <span class="text-xs sm:text-[13px] font-medium text-purple-700">{{ __('Administrator') }}</span>
            <div class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-purple-900 font-mono">{{ $stats['admins'] }}</div>
            <div class="mt-1 text-xs text-purple-600">{{ __('Akses sistem penuh') }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-2xs">
            <span class="text-xs sm:text-[13px] font-medium text-blue-700">{{ __('Operator') }}</span>
            <div class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-blue-900 font-mono">{{ $stats['operators'] }}</div>
            <div class="mt-1 text-xs text-blue-600">{{ __('Kontrol perintah & peringatan') }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-2xs">
            <span class="text-xs sm:text-[13px] font-medium text-slate-500">{{ __('Pengamat') }}</span>
            <div class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-slate-700 font-mono">{{ $stats['viewers'] }}</div>
            <div class="mt-1 text-xs text-slate-400">{{ __('Pemantauan hanya-baca') }}</div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-2xs overflow-hidden mb-6">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">{{ __('Direktori Pengguna Sistem') }}</h3>
            <span class="text-sm text-slate-500 font-mono">{{ $stats['total'] }} {{ __('Akun Terdaftar') }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50/80 text-slate-500 font-semibold border-b border-slate-100 text-xs uppercase">
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
                <tbody class="divide-y divide-slate-100 font-mono text-xs sm:text-sm">
                    @foreach($users as $user)
                        @php
                            $roleString = $user->role instanceof \BackedEnum ? $user->role->value : (string) $user->role;
                            $roleBadge = match($roleString) {
                                'admin' => 'bg-purple-50 text-purple-700 border-purple-200',
                                'operator' => 'bg-blue-50 text-blue-700 border-blue-200',
                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                            };
                            $roleDisplay = match($roleString) {
                                'admin' => __('Admin'),
                                'operator' => __('Operator'),
                                default => __('Pengamat'),
                            };
                            $isActive = (bool) ($user->is_active ?? true);
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition-colors h-[52px]">
                            <td class="py-3 px-4 font-sans font-medium text-slate-900">
                                {{ $user->name }}
                                @if(auth()->id() === $user->id)
                                    <span class="ml-1 text-xs text-blue-600 font-bold font-sans">({{ __('Anda') }})</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-600">{{ $user->email }}</td>
                            <td class="py-3 px-4 font-sans">
                                <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-bold uppercase tracking-wider border {{ $roleBadge }}">
                                    {{ $roleDisplay }}
                                </span>
                            </td>
                            <td class="py-3 px-4 font-sans">
                                @if($isActive)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                        {{ __('Aktif') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-1.5"></span>
                                        {{ __('Nonaktif') }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 font-sans">
                                @if($user->force_password_change)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                        {{ __('Wajib ganti kata sandi') }}
                                    </span>
                                @else
                                    <span class="text-slate-400 text-xs">{{ __('Normal') }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 font-sans text-slate-500 whitespace-nowrap">
                                {{ $user->created_at ? $user->created_at->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') . ' WIB' : '--' }}
                            </td>
                            <td class="py-3 px-4 font-sans text-right space-x-2 whitespace-nowrap">
                                @if(auth()->id() !== $user->id)
                                    <form method="POST" action="{{ route('user-management.toggle-status', $user) }}" class="inline-block" onsubmit="return confirm('{{ $isActive ? __('Apakah Anda yakin ingin menonaktifkan pengguna ini?') : __('Apakah Anda yakin ingin mengaktifkan kembali pengguna ini?') }}');">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center px-2.5 py-1.5 rounded-lg {{ $isActive ? 'bg-amber-50 hover:bg-amber-100 text-amber-700' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700' }} text-xs font-semibold cursor-pointer">
                                            {{ $isActive ? __('Nonaktifkan') : __('Aktifkan') }}
                                        </button>
                                    </form>
                                @endif

                                <button 
                                    type="button" 
                                    onclick="openEditUserModal(@js($user))"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold cursor-pointer"
                                >
                                    {{ __('Edit') }}
                                </button>

                                @if(auth()->id() !== $user->id)
                                    <form method="POST" action="{{ route('user-management.destroy', $user) }}" class="inline-block" onsubmit="return confirm('{{ __('Apakah Anda yakin ingin menghapus akun pengguna') }} {{ $user->email }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-700 text-xs font-semibold cursor-pointer">
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

    <!-- Security Policy Notice -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-2xs p-6">
        <h3 class="text-base font-bold text-slate-900 flex items-center space-x-2">
            <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <span>{{ __('Protokol Akses & Keamanan Industri') }}</span>
        </h3>
        <p class="text-sm text-slate-500 mt-1">
            {{ __('Sesuai dengan pedoman kontrol industri IEC 62443, aplikasi ini tidak menyediakan pendaftaran publik. Hanya administrator terotentikasi yang dapat menerbitkan kredensial operator atau pengamat. Semua peristiwa pembuatan, modifikasi, dan penghapusan pengguna diaudit secara permanen.') }}
        </p>
    </div>

    <!-- Modal: Create User -->
    <div id="addUserModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">{{ __('Buat Akun Pengguna') }}</h3>
                    <p class="text-xs sm:text-sm text-slate-400">{{ __('Terbitkan kredensial untuk operasi pelacak surya') }}</p>
                </div>
                <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('user-management.store') }}" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">{{ __('Nama Lengkap') }} *</label>
                    <input type="text" name="name" placeholder="misal: Budi Santoso" required class="w-full text-sm bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">{{ __('Alamat Email') }} *</label>
                    <input type="email" name="email" placeholder="nama@perusahaan.com" required class="w-full text-sm bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">{{ __('Peran Pengguna') }} *</label>
                    <select name="role" required class="w-full text-sm bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                        <option value="viewer">{{ __('Pengamat (Pemantauan hanya-baca)') }}</option>
                        <option value="operator" selected>{{ __('Operator (Gerak pelacak, mode aman, tindakan peringatan)') }}</option>
                        <option value="admin">{{ __('Admin (Administrasi penuh & manajemen pengguna)') }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">{{ __('Kata Sandi Awal') }} *</label>
                    <input type="password" name="password" minlength="8" required placeholder="••••••••" class="w-full text-sm bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    <span class="text-xs text-slate-400 mt-1 block">{{ __('Minimal 8 karakter') }}</span>
                </div>

                <div class="flex items-center space-x-2 pt-1">
                    <input type="checkbox" id="addIsActive" name="is_active" value="1" checked class="rounded text-blue-600 focus:ring-0 w-4 h-4 cursor-pointer">
                    <label for="addIsActive" class="text-sm text-slate-700 font-medium cursor-pointer">{{ __('Akun Aktif (dapat masuk ke sistem)') }}</label>
                </div>

                <div class="flex items-center space-x-2 pt-1">
                    <input type="checkbox" id="addForceChange" name="force_password_change" value="1" class="rounded text-blue-600 focus:ring-0 w-4 h-4 cursor-pointer">
                    <label for="addForceChange" class="text-sm text-slate-700 font-medium cursor-pointer">{{ __('Wajibkan reset kata sandi saat masuk pertama kali') }}</label>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')" class="px-5 py-2.5 rounded-lg border border-slate-300 text-sm font-semibold text-slate-700 hover:bg-slate-50 h-11 min-h-[44px] cursor-pointer">
                        {{ __('Batal') }}
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-xs h-11 min-h-[44px] cursor-pointer">
                        {{ __('Buat Akun') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit User & Reset Password -->
    <div id="editUserModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">{{ __('Edit Akun Pengguna') }}</h3>
                    <p class="text-xs sm:text-sm text-slate-400">{{ __('Modifikasi data pengguna, peran, status, atau reset kata sandi') }}</p>
                </div>
                <button type="button" onclick="document.getElementById('editUserModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="editUserForm" method="POST" action="" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">{{ __('Nama Lengkap') }} *</label>
                    <input type="text" id="editUserName" name="name" required class="w-full text-sm bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">{{ __('Alamat Email') }} *</label>
                    <input type="email" id="editUserEmail" name="email" required class="w-full text-sm bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                </div>

                <div id="editRoleContainer">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">{{ __('Peran Pengguna') }} *</label>
                    <select id="editUserRole" name="role" required class="w-full text-sm bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                        <option value="viewer">{{ __('Pengamat (Pemantauan hanya-baca)') }}</option>
                        <option value="operator">{{ __('Operator (Gerak pelacak, mode aman, tindakan peringatan)') }}</option>
                        <option value="admin">{{ __('Admin (Administrasi penuh & manajemen pengguna)') }}</option>
                    </select>
                </div>

                <div class="pt-2 border-t border-slate-100">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">{{ __('Reset Kata Sandi (kosongkan jika tidak diubah)') }}</label>
                    <input type="password" name="password" minlength="8" placeholder="••••••••" class="w-full text-sm bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    <span class="text-xs text-slate-400 mt-1 block">{{ __('Minimal 8 karakter baru jika ingin mereset kata sandi') }}</span>
                </div>

                <div class="flex items-center space-x-2 pt-1" id="editStatusContainer">
                    <input type="checkbox" id="editIsActive" name="is_active" value="1" class="rounded text-blue-600 focus:ring-0 w-4 h-4 cursor-pointer">
                    <label for="editIsActive" class="text-sm text-slate-700 font-medium cursor-pointer">{{ __('Akun Aktif (izinkan akses masuk)') }}</label>
                </div>

                <div class="flex items-center space-x-2 pt-1">
                    <input type="checkbox" id="editForceChange" name="force_password_change" value="1" class="rounded text-blue-600 focus:ring-0 w-4 h-4 cursor-pointer">
                    <label for="editForceChange" class="text-sm text-slate-700 font-medium cursor-pointer">{{ __('Wajibkan reset kata sandi saat masuk berikutnya') }}</label>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('editUserModal').classList.add('hidden')" class="px-5 py-2.5 rounded-lg border border-slate-300 text-sm font-semibold text-slate-700 hover:bg-slate-50 h-11 min-h-[44px] cursor-pointer">
                        {{ __('Batal') }}
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-xs h-11 min-h-[44px] cursor-pointer">
                        {{ __('Simpan Perubahan') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        const currentAuthId = {{ auth()->id() ?? 'null' }};

        function openEditUserModal(user) {
            document.getElementById('editUserForm').action = `/user-management/${user.id}`;
            document.getElementById('editUserName').value = user.name || '';
            document.getElementById('editUserEmail').value = user.email || '';
            const role = user.role && typeof user.role === 'object' ? user.role.value : user.role;
            document.getElementById('editUserRole').value = role || 'viewer';
            document.getElementById('editIsActive').checked = (user.is_active !== undefined) ? Boolean(user.is_active) : true;
            document.getElementById('editForceChange').checked = Boolean(user.force_password_change);

            // Prevent self-demotion or self-deactivation in modal
            const isSelf = user.id === currentAuthId;
            const roleSelect = document.getElementById('editUserRole');
            const statusCheckbox = document.getElementById('editIsActive');
            
            if (isSelf) {
                roleSelect.setAttribute('disabled', 'disabled');
                statusCheckbox.setAttribute('disabled', 'disabled');
            } else {
                roleSelect.removeAttribute('disabled');
                statusCheckbox.removeAttribute('disabled');
            }

            document.getElementById('editUserModal').classList.remove('hidden');
        }
    </script>
    @endpush
</x-layouts.app>
