<!DOCTYPE html>
<html lang="{{ app()->getLocale() == 'en' ? 'en' : 'id' }}" class="h-full bg-bg text-ink">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ strip_tags(__($title ?? 'Dashboard')) }} – {{ config('app.name', 'Solar Tracker') }}</title>

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

    <script>
        @php
            $locale = app()->getLocale();
            $jsonFile = base_path("lang/{$locale}.json");
            $translations = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];
        @endphp
        window.trans = @json($translations ?: (object)[]);
    </script>

    <!-- Vite Assets (Inter, JetBrains Mono, Alpine.js, Tailwind CSS) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased bg-bg text-ink" x-data="{ mobileSidebarOpen: false }">
    <div class="min-h-full flex flex-col lg:flex-row">
        <!-- Mobile Sidebar Backdrop -->
        <div 
            x-show="mobileSidebarOpen" 
            x-cloak
            x-transition:enter="transition-opacity ease-linear duration-150"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-40 lg:hidden"
            @click="mobileSidebarOpen = false"
        ></div>

        <!-- Sidebar Lebar ~220px -->
        <aside 
            :class="mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="fixed inset-y-0 left-0 z-50 w-[220px] bg-panel border-r border-line flex flex-col transition-transform duration-150 ease-in-out lg:static lg:translate-x-0 shrink-0 shadow-xs lg:shadow-none"
        >
            <!-- Brand / Logo -->
            <div class="h-14 flex items-center px-5 border-b border-line justify-between">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-blue-600 flex items-center justify-center text-white shrink-0 shadow-xs">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div class="truncate">
                        <div class="font-bold text-ink text-sm tracking-tight leading-tight">Solar Tracker</div>
                        <div class="text-[10px] font-medium text-muted tracking-wide">Monitoring Pintar</div>
                    </div>
                </a>
                <button @click="mobileSidebarOpen = false" class="lg:hidden text-muted hover:text-ink p-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Navigation Links (8 menu utama) -->
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <!-- 1. Dashboard -->
                <a 
                    href="{{ route('dashboard') }}" 
                    class="flex items-center px-3 py-2 text-xs sm:text-sm font-medium rounded-xl transition-colors {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-blue-950/40 dark:text-blue-400' : 'text-slate-600 hover:bg-panel-alt hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100' }}"
                >
                    <svg class="w-4 h-4 mr-2.5 shrink-0 {{ request()->routeIs('dashboard') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    <span>{{ __('Dashboard') }}</span>
                </a>

                <!-- 2. Solar Tracker -->
                <a 
                    href="{{ route('solar-tracker') }}" 
                    class="flex items-center px-3 py-2 text-xs sm:text-sm font-medium rounded-xl transition-colors {{ request()->routeIs('solar-tracker*') ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-blue-950/40 dark:text-blue-400' : 'text-slate-600 hover:bg-panel-alt hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100' }}"
                >
                    <svg class="w-4 h-4 mr-2.5 shrink-0 {{ request()->routeIs('solar-tracker*') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>{{ __('Solar Tracker') }}</span>
                </a>

                <!-- 3. Energi -->
                <a 
                    href="{{ route('energy-monitoring') }}" 
                    class="flex items-center px-3 py-2 text-xs sm:text-sm font-medium rounded-xl transition-colors {{ request()->routeIs('energy-monitoring*') ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-blue-950/40 dark:text-blue-400' : 'text-slate-600 hover:bg-panel-alt hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100' }}"
                >
                    <svg class="w-4 h-4 mr-2.5 shrink-0 {{ request()->routeIs('energy-monitoring*') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <span>{{ __('Energi') }}</span>
                </a>

                <!-- 4. Perangkat -->
                <a 
                    href="{{ route('device-monitoring') }}" 
                    class="flex items-center px-3 py-2 text-xs sm:text-sm font-medium rounded-xl transition-colors {{ request()->routeIs('device-monitoring*') ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-blue-950/40 dark:text-blue-400' : 'text-slate-600 hover:bg-panel-alt hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100' }}"
                >
                    <svg class="w-4 h-4 mr-2.5 shrink-0 {{ request()->routeIs('device-monitoring*') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                    </svg>
                    <span>{{ __('Perangkat') }}</span>
                </a>

                <!-- 5. Telemetri -->
                <a 
                    href="{{ route('telemetry') }}" 
                    class="flex items-center px-3 py-2 text-xs sm:text-sm font-medium rounded-xl transition-colors {{ request()->routeIs('telemetry*') ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-blue-950/40 dark:text-blue-400' : 'text-slate-600 hover:bg-panel-alt hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100' }}"
                >
                    <svg class="w-4 h-4 mr-2.5 shrink-0 {{ request()->routeIs('telemetry*') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                    </svg>
                    <span>{{ __('Telemetri') }}</span>
                </a>

                <!-- 6. Alert -->
                <a 
                    href="{{ route('alerts') }}" 
                    class="flex items-center px-3 py-2 text-xs sm:text-sm font-medium rounded-xl transition-colors {{ request()->routeIs('alerts*') ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-blue-950/40 dark:text-blue-400' : 'text-slate-600 hover:bg-panel-alt hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100' }}"
                >
                    <svg class="w-4 h-4 mr-2.5 shrink-0 {{ request()->routeIs('alerts*') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span>{{ __('Alert') }}</span>
                </a>

                <!-- 7. Data Historis -->
                <a 
                    href="{{ route('historical-data') }}" 
                    class="flex items-center px-3 py-2 text-xs sm:text-sm font-medium rounded-xl transition-colors {{ request()->routeIs('historical-data*') ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-blue-950/40 dark:text-blue-400' : 'text-slate-600 hover:bg-panel-alt hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100' }}"
                >
                    <svg class="w-4 h-4 mr-2.5 shrink-0 {{ request()->routeIs('historical-data*') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ __('Data Historis') }}</span>
                </a>

                <!-- 8. Laporan -->
                <a 
                    href="{{ route('reports') }}" 
                    class="flex items-center px-3 py-2 text-xs sm:text-sm font-medium rounded-xl transition-colors {{ request()->routeIs('reports*') ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-blue-950/40 dark:text-blue-400' : 'text-slate-600 hover:bg-panel-alt hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100' }}"
                >
                    <svg class="w-4 h-4 mr-2.5 shrink-0 {{ request()->routeIs('reports*') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>{{ __('Laporan') }}</span>
                </a>

                <!-- Administration section -->
                @canany(['manage-devices', 'admin'])
                    <div class="pt-4 pb-1">
                        <p class="px-3 text-[11px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">{{ __('Administrasi') }}</p>
                    </div>
                @endcanany

                @can('manage-devices')
                <a 
                    href="{{ route('device-management') }}" 
                    class="flex items-center px-3 py-2 text-xs sm:text-sm font-medium rounded-xl transition-colors {{ request()->routeIs('device-management*') ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-blue-950/40 dark:text-blue-400' : 'text-slate-600 hover:bg-panel-alt hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100' }}"
                >
                    <svg class="w-4 h-4 mr-2.5 shrink-0 {{ request()->routeIs('device-management*') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>{{ __('Manajemen Perangkat') }}</span>
                </a>
                @endcan

                @can('admin')
                <a 
                    href="{{ route('user-management') }}" 
                    class="flex items-center px-3 py-2 text-xs sm:text-sm font-medium rounded-xl transition-colors {{ request()->routeIs('user-management*') ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-blue-950/40 dark:text-blue-400' : 'text-slate-600 hover:bg-panel-alt hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100' }}"
                >
                    <svg class="w-4 h-4 mr-2.5 shrink-0 {{ request()->routeIs('user-management*') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>{{ __('Manajemen Pengguna') }}</span>
                </a>
                @endcan

                @can('operator')
                <a 
                    href="{{ route('settings') }}" 
                    class="flex items-center px-3 py-2 text-xs sm:text-sm font-medium rounded-xl transition-colors {{ request()->routeIs('settings*') ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-blue-950/40 dark:text-blue-400' : 'text-slate-600 hover:bg-panel-alt hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100' }}"
                >
                    <svg class="w-4 h-4 mr-2.5 shrink-0 {{ request()->routeIs('settings*') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                    </svg>
                    <span>{{ __('Pengaturan') }}</span>
                </a>
                @endcan
            </nav>

            <!-- User Info & Logout footer -->
            <div class="p-3 border-t border-line bg-panel-alt/50">
                <div class="flex items-center justify-between p-2 rounded-xl bg-panel border border-line">
                    <div class="flex items-center gap-2.5 overflow-hidden">
                        <div class="w-7 h-7 rounded-lg bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-400 flex items-center justify-center font-bold text-xs shrink-0">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="truncate">
                            <div class="text-xs font-semibold text-ink truncate leading-tight">{{ auth()->user()->name ?? 'User' }}</div>
                            <div class="text-[10px] text-muted capitalize">
                                {{ auth()->user()->role instanceof \BackedEnum ? auth()->user()->role->value : (string) (auth()->user()->role ?? 'viewer') }}
                            </div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button 
                            id="btn-logout"
                            type="submit" 
                            title="{{ __('Keluar') }}"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Header Bersih -->
            <header class="h-14 bg-panel border-b border-line px-4 sm:px-6 flex items-center justify-between z-10 sticky top-0 shadow-2xs">
                <div class="flex items-center gap-3">
                    <button 
                        @click="mobileSidebarOpen = true" 
                        type="button" 
                        class="lg:hidden p-1.5 rounded-xl text-muted hover:text-ink hover:bg-panel-alt focus:outline-none"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <!-- Site name / location indicator -->
                    <div class="flex items-center gap-2 text-xs sm:text-sm font-medium">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span class="font-semibold text-ink">Cikarang Industrial Solar Pilot</span>
                    </div>
                </div>

                <!-- Header Right: Jam WIB (window.wibClock()) & Theme Switcher & Status -->
                <div class="flex items-center gap-2.5 sm:gap-3.5">
                    <!-- Jam WIB live (Alpine.js) -->
                    <div x-data="wibClock()" class="flex items-center gap-2 text-xs sm:text-sm">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-ink font-mono font-semibold tabular-nums" x-text="timeString">--.--.-- WIB</span>
                        <span class="hidden md:inline text-muted text-xs" x-text="dateString">---</span>
                    </div>

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

                    <div class="h-4 w-px bg-line hidden sm:block"></div>

                    <!-- Telemetry Live Status -->
                    <div class="hidden sm:flex items-center gap-1.5 text-xs text-emerald-700 bg-emerald-50 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/60 px-2.5 py-0.5 rounded-full font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span class="text-[11px] font-semibold">LIVE</span>
                    </div>

                    <!-- User Role Pill -->
                    @php
                        $roleVal = auth()->user()?->role instanceof \BackedEnum ? auth()->user()->role->value : (string) (auth()->user()?->role ?? 'viewer');
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-wider border border-blue-200 dark:border-blue-800/60 bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400">
                        {{ $roleVal }}
                    </span>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-7 bg-bg text-ink">
                {{ $slot }}
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
