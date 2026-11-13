<!DOCTYPE html>
<html lang="{{ app()->getLocale() == 'en' ? 'en' : 'id' }}" class="h-full bg-bg text-ink">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('Sign In') }} – {{ config('app.name', 'Solar Tracker') }}</title>

    <!-- Anti-FOUC Theme Initialization Script -->
    <script>
        (function () {
            try {
                var theme = localStorage.getItem('theme') || 'system';
                var isDark = false;
                if (theme === 'dark') {
                    isDark = true;
                } else if (theme === 'light') {
                    isDark = false;
                } else {
                    isDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                }
                if (isDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark');
                }
            }
        })();
    </script>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased bg-bg text-ink flex flex-col justify-between" x-data="{ requestAccessModal: false }">
    <!-- Top Bar -->
    <header class="w-full px-6 py-4 flex items-center justify-between border-b border-line bg-panel shadow-2xs">
        <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center text-white shadow-xs">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <div>
                <span class="text-sm font-bold text-ink tracking-tight">Solar Tracker</span>
                <span class="text-xs text-muted font-mono ml-2 hidden sm:inline">v2.4 &middot; Industrial SCADA</span>
            </div>
        </div>
        <div class="flex items-center space-x-3">
            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-mono font-medium border border-emerald-200 dark:border-emerald-800/60 text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-2 animate-pulse"></span>
                {{ __('System Operational') }}
            </span>

            <!-- Theme Switcher Dropdown (Terang, Gelap, Otomatis) -->
            <div x-data="themeDropdown()" class="relative">
                <button 
                    @click="open = !open" 
                    type="button" 
                    aria-label="Ganti tema"
                    title="{{ __('Ganti tema') }}"
                    class="p-1.5 sm:p-2 rounded-xl border border-line bg-panel hover:bg-panel-alt text-muted hover:text-ink transition-colors flex items-center justify-center focus:outline-none cursor-pointer"
                >
                    <!-- Sun icon when theme is light or system-light -->
                    <template x-if="theme === 'light' || (theme === 'system' && !isDark)">
                        <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <circle cx="12" cy="12" r="4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>
                        </svg>
                    </template>
                    <!-- Moon icon when theme is dark or system-dark -->
                    <template x-if="theme === 'dark' || (theme === 'system' && isDark)">
                        <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-width="2" stroke-linecap="round" d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
                        </svg>
                    </template>
                </button>

                <!-- Menu kecil pilihan tema -->
                <div 
                    x-show="open" 
                    @click.outside="open = false" 
                    x-cloak 
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute right-0 mt-2 w-36 rounded-2xl bg-panel border border-line shadow-lg py-1.5 z-50 text-xs font-medium text-ink"
                >
                    <!-- Pilihan Terang -->
                    <button 
                        type="button" 
                        @click="setTheme('light')" 
                        class="w-full flex items-center justify-between px-3 py-2 hover:bg-panel-alt transition-colors text-left cursor-pointer"
                        :class="theme === 'light' ? 'text-accent font-semibold' : 'text-ink'"
                    >
                        <span class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <circle cx="12" cy="12" r="4" stroke-width="2"/>
                                <path stroke-width="2" stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>
                            </svg>
                            <span>Terang</span>
                        </span>
                        <template x-if="theme === 'light'">
                            <svg class="w-3.5 h-3.5 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </template>
                    </button>

                    <!-- Pilihan Gelap -->
                    <button 
                        type="button" 
                        @click="setTheme('dark')" 
                        class="w-full flex items-center justify-between px-3 py-2 hover:bg-panel-alt transition-colors text-left cursor-pointer"
                        :class="theme === 'dark' ? 'text-accent font-semibold' : 'text-ink'"
                    >
                        <span class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-width="2" stroke-linecap="round" d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
                            </svg>
                            <span>Gelap</span>
                        </span>
                        <template x-if="theme === 'dark'">
                            <svg class="w-3.5 h-3.5 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </template>
                    </button>

                    <!-- Pilihan Otomatis -->
                    <button 
                        type="button" 
                        @click="setTheme('system')" 
                        class="w-full flex items-center justify-between px-3 py-2 hover:bg-panel-alt transition-colors text-left cursor-pointer"
                        :class="theme === 'system' ? 'text-accent font-semibold' : 'text-ink'"
                    >
                        <span class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <rect x="2" y="3" width="20" height="14" rx="2" stroke-width="2"/>
                                <path stroke-width="2" stroke-linecap="round" d="M8 21h8M12 17v4"/>
                            </svg>
                            <span>Otomatis</span>
                        </span>
                        <template x-if="theme === 'system'">
                            <svg class="w-3.5 h-3.5 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 flex items-center justify-center px-4 py-12 sm:px-6">
        <div class="w-full max-w-md">
            <!-- Card -->
            <div class="bg-panel rounded-2xl border border-line p-7 sm:p-9 shadow-xs">
                <!-- Header -->
                <div class="text-center mb-7">
                    <div class="inline-flex w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400 items-center justify-center mb-3.5 shadow-2xs">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-ink">{{ __('Sign in to your account') }}</h2>
                    <p class="text-xs text-muted mt-1">{{ __('Dual-Axis Solar Tracker Monitoring & Control') }}</p>
                </div>

                <!-- Session Status / Errors -->
                @if (session('status'))
                    <div class="mb-5 p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-xs text-emerald-800 dark:text-emerald-300">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-xs text-rose-800 dark:text-rose-300">
                        <div class="font-semibold mb-1">{{ __('Please check the errors below:') }}</div>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Login Form -->
                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf

                    <!-- Email Input -->
                    <div>
                        <label for="email" class="block text-xs font-semibold text-muted uppercase tracking-wider mb-1.5">
                            {{ __('Work Email') }}
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-muted">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>
                            <input 
                                id="email" 
                                type="email" 
                                name="email" 
                                value="{{ old('email') }}" 
                                required 
                                autofocus 
                                autocomplete="username"
                                placeholder="nama@perusahaan.com"
                                class="w-full pl-10 pr-3.5 py-2.5 h-11 text-sm rounded-xl bg-panel border border-line text-ink placeholder-muted focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:focus:ring-blue-900/30 transition-all shadow-2xs"
                            >
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-xs font-semibold text-muted uppercase tracking-wider">
                                {{ __('Password') }}
                            </label>
                            @if (Route::has('password.request'))
                                <a 
                                    href="{{ route('password.request') }}" 
                                    class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium transition-colors"
                                >
                                    {{ __('Forgot password?') }}
                                </a>
                            @endif
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-muted">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input 
                                id="password" 
                                type="password" 
                                name="password" 
                                required 
                                autocomplete="current-password"
                                placeholder="••••••••••••"
                                class="w-full pl-10 pr-3.5 py-2.5 h-11 text-sm rounded-xl bg-panel border border-line text-ink placeholder-muted focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:focus:ring-blue-900/30 transition-all shadow-2xs"
                            >
                        </div>
                    </div>

                    <!-- Remember Device -->
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center cursor-pointer">
                            <input 
                                type="checkbox" 
                                name="remember" 
                                id="remember"
                                class="w-4 h-4 rounded border-line text-blue-600 focus:ring-0 cursor-pointer"
                            >
                            <span class="ml-2.5 text-xs text-muted select-none font-medium">{{ __('Remember this device') }}</span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            class="w-full flex items-center justify-center h-11 py-2.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition-colors shadow-xs cursor-pointer"
                        >
                            <span>{{ __('Sign In') }}</span>
                            <svg class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </div>
                </form>

                <!-- Request Access Divider -->
                <div class="mt-6 pt-5 border-t border-line text-center">
                    <p class="text-xs text-muted">
                        {{ __('Need access to this tracker?') }}
                        <button 
                            type="button" 
                            @click="requestAccessModal = true" 
                            class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-semibold cursor-pointer ml-1"
                        >
                            {{ __('Contact your administrator') }}
                        </button>
                    </p>
                </div>
            </div>
        </div>
    </main>

    <!-- Request Access Modal -->
    <div 
        x-show="requestAccessModal" 
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center"
        aria-modal="true"
    >
        <div 
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
            @click="requestAccessModal = false"
        ></div>

        <div class="relative bg-panel rounded-2xl max-w-md w-full p-6 sm:p-7 border border-line text-center shadow-xl text-ink">
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400 mx-auto flex items-center justify-center mb-4 shadow-2xs">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <h3 class="text-base font-bold text-ink mb-1.5">{{ __('Request Account Access') }}</h3>
            <p class="text-xs text-muted leading-relaxed mb-5">
                {{ __('Registration is managed by site administrators. To request an account or change role permissions (Operator or Viewer), please contact your system administrator at:') }}
                <br>
                <span class="font-mono text-blue-600 dark:text-blue-400 font-semibold bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/60 px-3 py-1 rounded-lg inline-block mt-3">admin@perusahaan.com</span>
            </p>
            <button 
                type="button" 
                @click="requestAccessModal = false"
                class="w-full h-10 px-4 rounded-xl bg-panel-alt hover:bg-slate-200 dark:hover:bg-slate-700 text-ink border border-line text-xs font-semibold transition-colors cursor-pointer"
            >
                {{ __('Close') }}
            </button>
        </div>
    </div>

    <!-- Footer -->
    <footer class="w-full py-4 px-6 text-center text-xs text-muted border-t border-line bg-panel font-mono">
        Solar Tracker &copy; {{ date('Y') }} Industrial Solar Monitoring Pilot &bull; Dual-Axis PV Platform
    </footer>
</body>
</html>
