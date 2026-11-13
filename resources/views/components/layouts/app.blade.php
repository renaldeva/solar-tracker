<!DOCTYPE html>
<html lang="{{ app()->getLocale() == 'en' ? 'en' : 'id' }}" class="h-full bg-bg text-ink">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ strip_tags(__($title ?? 'Dashboard')) }} – {{ config('app.name', 'Solar Tracker') }}</title>

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
            class="fixed inset-0 bg-bg/80 z-40 lg:hidden"
            @click="mobileSidebarOpen = false"
        ></div>

        <!-- Sidebar sempit kiri -->
        <aside 
            :class="mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="fixed inset-y-0 left-0 z-50 w-56 bg-panel border-r border-line flex flex-col transition-transform duration-150 ease-in-out lg:static lg:translate-x-0 shrink-0"
        >
            <!-- Brand / Logo -->
            <div class="h-12 flex items-center px-4 border-b border-line justify-between">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                    <div class="w-6 h-6 rounded bg-accent flex items-center justify-center text-ink shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div class="truncate">
                        <div class="font-bold text-ink text-xs tracking-tight leading-tight">Solar Tracker</div>
                        <div class="text-[9px] font-mono font-medium text-muted uppercase tracking-wider">Industrial Pilot</div>
                    </div>
                </a>
                <button @click="mobileSidebarOpen = false" class="lg:hidden text-muted hover:text-ink">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Navigation Links (8 menu utama) -->
            <nav class="flex-1 px-2 py-3 space-y-0.5 overflow-y-auto">
                <!-- 1. Dashboard -->
                <a 
                    href="{{ route('dashboard') }}" 
                    class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded transition-colors {{ request()->routeIs('dashboard') ? 'bg-accent/15 text-accent border border-accent/30 font-semibold' : 'text-muted hover:bg-line/30 hover:text-ink border border-transparent' }}"
                >
                    <svg class="w-4 h-4 mr-2.5 shrink-0 {{ request()->routeIs('dashboard') ? 'text-accent' : 'text-muted' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    <span>{{ __('Dashboard') }}</span>
                </a>

                <!-- 2. Solar Tracker -->
                <a 
                    href="{{ route('solar-tracker') }}" 
                    class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded transition-colors {{ request()->routeIs('solar-tracker*') ? 'bg-accent/15 text-accent border border-accent/30 font-semibold' : 'text-muted hover:bg-line/30 hover:text-ink border border-transparent' }}"
                >
                    <svg class="w-4 h-4 mr-2.5 shrink-0 {{ request()->routeIs('solar-tracker*') ? 'text-accent' : 'text-muted' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>{{ __('Solar Tracker') }}</span>
                </a>

                <!-- 3. Energi -->
                <a 
                    href="{{ route('energy-monitoring') }}" 
                    class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded transition-colors {{ request()->routeIs('energy-monitoring*') ? 'bg-accent/15 text-accent border border-accent/30 font-semibold' : 'text-muted hover:bg-line/30 hover:text-ink border border-transparent' }}"
                >
                    <svg class="w-4 h-4 mr-2.5 shrink-0 {{ request()->routeIs('energy-monitoring*') ? 'text-accent' : 'text-muted' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <span>{{ __('Energi') }}</span>
                </a>

                <!-- 4. Perangkat -->
                <a 
                    href="{{ route('device-monitoring') }}" 
                    class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded transition-colors {{ request()->routeIs('device-monitoring*') ? 'bg-accent/15 text-accent border border-accent/30 font-semibold' : 'text-muted hover:bg-line/30 hover:text-ink border border-transparent' }}"
                >
                    <svg class="w-4 h-4 mr-2.5 shrink-0 {{ request()->routeIs('device-monitoring*') ? 'text-accent' : 'text-muted' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                    </svg>
                    <span>{{ __('Perangkat') }}</span>
                </a>

                <!-- 5. Telemetri -->
                <a 
                    href="{{ route('telemetry') }}" 
                    class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded transition-colors {{ request()->routeIs('telemetry*') ? 'bg-accent/15 text-accent border border-accent/30 font-semibold' : 'text-muted hover:bg-line/30 hover:text-ink border border-transparent' }}"
                >
                    <svg class="w-4 h-4 mr-2.5 shrink-0 {{ request()->routeIs('telemetry*') ? 'text-accent' : 'text-muted' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                    </svg>
                    <span>{{ __('Telemetri') }}</span>
                </a>

                <!-- 6. Alert -->
                <a 
                    href="{{ route('alerts') }}" 
                    class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded transition-colors {{ request()->routeIs('alerts*') ? 'bg-accent/15 text-accent border border-accent/30 font-semibold' : 'text-muted hover:bg-line/30 hover:text-ink border border-transparent' }}"
                >
                    <svg class="w-4 h-4 mr-2.5 shrink-0 {{ request()->routeIs('alerts*') ? 'text-accent' : 'text-muted' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span>{{ __('Alert') }}</span>
                </a>

                <!-- 7. Data Historis -->
                <a 
                    href="{{ route('historical-data') }}" 
                    class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded transition-colors {{ request()->routeIs('historical-data*') ? 'bg-accent/15 text-accent border border-accent/30 font-semibold' : 'text-muted hover:bg-line/30 hover:text-ink border border-transparent' }}"
                >
                    <svg class="w-4 h-4 mr-2.5 shrink-0 {{ request()->routeIs('historical-data*') ? 'text-accent' : 'text-muted' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ __('Data Historis') }}</span>
                </a>

                <!-- 8. Laporan -->
                <a 
                    href="{{ route('reports') }}" 
                    class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded transition-colors {{ request()->routeIs('reports*') ? 'bg-accent/15 text-accent border border-accent/30 font-semibold' : 'text-muted hover:bg-line/30 hover:text-ink border border-transparent' }}"
                >
                    <svg class="w-4 h-4 mr-2.5 shrink-0 {{ request()->routeIs('reports*') ? 'text-accent' : 'text-muted' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>{{ __('Laporan') }}</span>
                </a>

                <!-- Administration section -->
                @canany(['manage-devices', 'admin'])
                    <div class="pt-3 pb-1">
                        <p class="px-2.5 text-[10px] font-mono font-semibold text-muted uppercase tracking-wider">{{ __('Administrasi') }}</p>
                    </div>
                @endcanany

                @can('manage-devices')
                <a 
                    href="{{ route('device-management') }}" 
                    class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded transition-colors {{ request()->routeIs('device-management*') ? 'bg-accent/15 text-accent border border-accent/30 font-semibold' : 'text-muted hover:bg-line/30 hover:text-ink border border-transparent' }}"
                >
                    <svg class="w-4 h-4 mr-2.5 shrink-0 {{ request()->routeIs('device-management*') ? 'text-accent' : 'text-muted' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>{{ __('Manajemen Perangkat') }}</span>
                </a>
                @endcan

                @can('admin')
                <a 
                    href="{{ route('user-management') }}" 
                    class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded transition-colors {{ request()->routeIs('user-management*') ? 'bg-accent/15 text-accent border border-accent/30 font-semibold' : 'text-muted hover:bg-line/30 hover:text-ink border border-transparent' }}"
                >
                    <svg class="w-4 h-4 mr-2.5 shrink-0 {{ request()->routeIs('user-management*') ? 'text-accent' : 'text-muted' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>{{ __('Manajemen Pengguna') }}</span>
                </a>
                @endcan

                @can('operator')
                <a 
                    href="{{ route('settings') }}" 
                    class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded transition-colors {{ request()->routeIs('settings*') ? 'bg-accent/15 text-accent border border-accent/30 font-semibold' : 'text-muted hover:bg-line/30 hover:text-ink border border-transparent' }}"
                >
                    <svg class="w-4 h-4 mr-2.5 shrink-0 {{ request()->routeIs('settings*') ? 'text-accent' : 'text-muted' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                    </svg>
                    <span>{{ __('Pengaturan') }}</span>
                </a>
                @endcan
            </nav>

            <!-- User Info & Logout footer -->
            <div class="p-2 border-t border-line bg-bg/40">
                <div class="flex items-center justify-between p-2 rounded bg-panel border border-line">
                    <div class="flex items-center gap-2 overflow-hidden">
                        <div class="w-6 h-6 rounded bg-line/60 text-ink flex items-center justify-center font-mono font-bold text-[11px] shrink-0">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="truncate">
                            <div class="text-xs font-medium text-ink truncate leading-tight">{{ auth()->user()->name ?? 'User' }}</div>
                            <div class="text-[9px] font-mono text-muted uppercase tracking-wider">
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
                            class="p-1 rounded text-muted hover:text-danger hover:bg-danger/10 transition-colors"
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
            <!-- Header tipis -->
            <header class="h-12 bg-panel border-b border-line px-4 sm:px-6 flex items-center justify-between z-10 sticky top-0">
                <div class="flex items-center gap-3">
                    <button 
                        @click="mobileSidebarOpen = true"
                        type="button" 
                        class="lg:hidden p-1.5 rounded text-muted hover:text-ink hover:bg-line/40 focus:outline-none"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <!-- Site indicator: status titik + teks -->
                    <div class="hidden sm:flex items-center gap-2 text-xs font-mono text-muted">
                        <span class="w-2 h-2 rounded-full bg-ok"></span>
                        <span class="text-ink font-medium">Cikarang Industrial Pilot</span>
                    </div>
                </div>

                <!-- Header Right: Jam WIB (window.wibClock()) & Status -->
                <div class="flex items-center gap-3 sm:gap-4">
                    <!-- Jam WIB live (Alpine.js) -->
                    <div x-data="wibClock()" class="flex items-center gap-2 text-xs font-mono">
                        <span class="w-1.5 h-1.5 rounded-full bg-ok animate-pulse"></span>
                        <span class="text-ink font-semibold tabular-nums" x-text="timeString">--.--.-- WIB</span>
                        <span class="hidden md:inline text-muted text-[11px]" x-text="dateString">---</span>
                    </div>

                    <div class="h-4 w-px bg-line hidden sm:block"></div>

                    <!-- Telemetry Live Status -->
                    <div class="hidden sm:flex items-center gap-1.5 text-xs font-mono text-muted bg-bg border border-line px-2 py-0.5 rounded">
                        <span class="w-1.5 h-1.5 rounded-full bg-ok"></span>
                        <span class="text-[11px] text-ink font-semibold">LIVE</span>
                    </div>

                    <!-- User Role Pill -->
                    @php
                        $roleVal = auth()->user()?->role instanceof \BackedEnum ? auth()->user()->role->value : (string) (auth()->user()?->role ?? 'viewer');
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase tracking-wider border border-line bg-bg text-muted">
                        {{ $roleVal }}
                    </span>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 bg-bg text-ink">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
