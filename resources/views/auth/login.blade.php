<!DOCTYPE html>
<html lang="{{ app()->getLocale() == 'en' ? 'en' : 'id' }}" class="h-full bg-bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('Sign In') }} – {{ config('app.name', 'Solar Tracker') }}</title>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased bg-bg text-ink flex flex-col justify-between" x-data="{ requestAccessModal: false }">
    <!-- Top Bar -->
    <header class="w-full px-6 py-3.5 flex items-center justify-between border-b border-line bg-panel">
        <div class="flex items-center space-x-3">
            <div class="w-8 h-8 rounded-md bg-accent flex items-center justify-center text-ink">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <div>
                <span class="text-sm font-semibold text-ink tracking-tight">Solar Tracker</span>
                <span class="text-xs text-muted font-mono ml-2 hidden sm:inline">v2.4 &middot; Industrial SCADA</span>
            </div>
        </div>
        <div class="flex items-center space-x-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono border border-ok/30 text-ok bg-ok/10">
                <span class="w-1.5 h-1.5 rounded-full bg-ok mr-1.5 animate-pulse"></span>
                {{ __('System Operational') }}
            </span>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 flex items-center justify-center px-4 py-10 sm:px-6">
        <div class="w-full max-w-md">
            <!-- Card -->
            <div class="bg-panel rounded-md border border-line p-6 sm:p-8">
                <!-- Header -->
                <div class="text-center mb-6">
                    <div class="inline-flex w-12 h-12 rounded-md bg-accent/10 border border-accent/30 text-accent items-center justify-center mb-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <h2 class="text-lg font-semibold text-ink">{{ __('Sign in to your account') }}</h2>
                    <p class="text-xs text-muted mt-1">{{ __('Dual-Axis Solar Tracker Monitoring & Control') }}</p>
                </div>

                <!-- Session Status / Errors -->
                @if (session('status'))
                    <div class="mb-4 p-3 rounded-md bg-ok/10 border border-ok/30 text-xs text-ok">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 p-3 rounded-md bg-danger/10 border border-danger/30 text-xs text-danger">
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
                        <label for="email" class="block text-xs font-semibold text-muted uppercase tracking-wider mb-1">
                            {{ __('Work Email') }}
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted">
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
                                autocomplete="email"
                                placeholder="nama@perusahaan.com"
                                class="w-full pl-9 pr-3 py-2 h-10 text-sm rounded-md bg-bg border border-line text-ink placeholder-muted focus:outline-none focus:border-accent transition-colors"
                            >
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="password" class="block text-xs font-semibold text-muted uppercase tracking-wider">
                                {{ __('Password') }}
                            </label>
                            <a href="{{ route('password.request') }}" class="text-xs text-accent hover:underline">
                                {{ __('Forgot password?') }}
                            </a>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted">
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
                                class="w-full pl-9 pr-3 py-2 h-10 text-sm rounded-md bg-bg border border-line text-ink placeholder-muted focus:outline-none focus:border-accent transition-colors"
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
                                class="w-4 h-4 rounded border-line bg-bg text-accent focus:ring-0 cursor-pointer"
                            >
                            <span class="ml-2 text-xs text-muted select-none">{{ __('Remember this device') }}</span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            class="w-full flex items-center justify-center h-10 py-2 px-4 rounded-md bg-accent hover:bg-blue-600 text-ink text-sm font-medium transition-colors cursor-pointer"
                        >
                            <span>{{ __('Sign In') }}</span>
                            <svg class="w-4 h-4 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </div>
                </form>

                <!-- Request Access Divider -->
                <div class="mt-6 pt-4 border-t border-line text-center">
                    <p class="text-xs text-muted">
                        {{ __('Need access to this tracker?') }}
                        <button 
                            type="button"
                            @click="requestAccessModal = true" 
                            class="text-accent hover:underline font-medium cursor-pointer ml-1"
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
            class="fixed inset-0 bg-black/60 transition-opacity"
            @click="requestAccessModal = false"
        ></div>

        <div class="relative bg-panel rounded-md max-w-md w-full p-6 border border-line text-center">
            <div class="w-12 h-12 rounded-md bg-accent/10 border border-accent/30 text-accent mx-auto flex items-center justify-center mb-3">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <h3 class="text-base font-semibold text-ink mb-1.5">{{ __('Request Account Access') }}</h3>
            <p class="text-xs text-muted leading-relaxed mb-4">
                {{ __('Registration is managed by site administrators. To request an account or change role permissions (Operator or Viewer), please contact your system administrator at:') }}
                <br>
                <span class="font-mono text-accent bg-bg border border-line px-2 py-0.5 rounded inline-block mt-2">admin@perusahaan.com</span>
            </p>
            <button 
                type="button" 
                @click="requestAccessModal = false"
                class="w-full h-9 px-4 rounded-md bg-bg hover:bg-line/40 text-ink border border-line text-xs font-medium transition-colors cursor-pointer"
            >
                {{ __('Close') }}
            </button>
        </div>
    </div>

    <!-- Footer -->
    <footer class="w-full py-3 px-6 text-center text-xs text-muted border-t border-line bg-panel font-mono">
        Solar Tracker &copy; {{ date('Y') }} Industrial Solar Monitoring Pilot &bull; Dual-Axis PV Platform
    </footer>
</body>
</html>
