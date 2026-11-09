<!DOCTYPE html>
<html lang="{{ app()->getLocale() == 'en' ? 'en' : 'id' }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('Sign In') }} – {{ config('app.name', 'Solar Tracker') }}</title>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased bg-slate-50 text-slate-900 flex flex-col justify-between" x-data="{ requestAccessModal: false }">
    <!-- Top Bar -->
    <header class="w-full px-6 py-4 flex items-center justify-between border-b border-slate-200/80 bg-white/70 backdrop-blur-xs">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white shadow-xs">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <div>
                <span class="text-base font-bold text-slate-900 tracking-tight">Solar Tracker</span>
                <span class="text-xs text-slate-500 font-medium ml-1.5 hidden sm:inline">Industrial Monitoring Pilot</span>
            </div>
        </div>
        <div class="flex items-center space-x-2">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2 animate-pulse"></span>
                {{ __('System Operational') }}
            </span>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 flex items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="w-full max-w-md">
            <!-- Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-md shadow-slate-100 p-8 sm:p-10">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="inline-flex w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 items-center justify-center mb-3">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900">{{ __('Sign in to your account') }}</h2>
                    <p class="text-sm text-slate-500 mt-1.5">{{ __('Dual-Axis Solar Tracker Monitoring & Control') }}</p>
                </div>

                <!-- Session Status / Errors -->
                @if (session('status'))
                    <div class="mb-5 p-3.5 rounded-lg bg-emerald-50 border border-emerald-200 text-sm text-emerald-700">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 p-4 rounded-lg bg-rose-50 border border-rose-200 text-sm text-rose-700">
                        <div class="font-semibold mb-1">{{ __('Please check the errors below:') }}</div>
                        <ul class="list-disc list-inside space-y-0.5 text-xs sm:text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Login Form (Inputs min-height 44px, text 16px) -->
                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <!-- Email Input -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-slate-700 mb-1.5">
                            {{ __('Work Email') }}
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                                class="w-full pl-11 pr-4 py-2.5 h-11 min-h-[44px] text-base rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors placeholder:text-slate-400"
                            >
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-sm font-semibold text-slate-700">
                                {{ __('Password') }}
                            </label>
                            <a href="{{ route('password.request') }}" class="text-xs sm:text-sm text-blue-600 hover:text-blue-700 font-medium">
                                {{ __('Forgot password?') }}
                            </a>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                                class="w-full pl-11 pr-4 py-2.5 h-11 min-h-[44px] text-base rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors placeholder:text-slate-400"
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
                                class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500 cursor-pointer"
                            >
                            <span class="ml-2 text-sm text-slate-600 select-none">{{ __('Remember this device') }}</span>
                        </label>
                    </div>

                    <!-- Submit Button (Min height 44px, text 16px) -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            class="w-full flex items-center justify-center h-11 min-h-[44px] py-2.5 px-4 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-base font-semibold shadow-xs focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all cursor-pointer"
                        >
                            <span>{{ __('Sign In') }}</span>
                            <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </div>
                </form>

                <!-- Request Access Divider -->
                <div class="mt-6 pt-6 border-t border-slate-100 text-center">
                    <p class="text-sm text-slate-500">
                        {{ __('Need access to this tracker?') }}
                        <button 
                            type="button"
                            @click="requestAccessModal = true" 
                            class="text-blue-600 hover:text-blue-700 font-semibold cursor-pointer underline ml-1"
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
            class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity"
            @click="requestAccessModal = false"
        ></div>

        <div class="relative bg-white rounded-2xl max-w-md w-full p-6 sm:p-8 shadow-xl border border-slate-200 transform transition-all text-center">
            <div class="w-14 h-14 rounded-full bg-blue-50 text-blue-600 mx-auto flex items-center justify-center mb-4">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-900 mb-2">{{ __('Request Account Access') }}</h3>
            <p class="text-sm text-slate-600 leading-relaxed mb-6">
                {{ __('Registration is managed by site administrators. To request an account or change role permissions (Operator or Viewer), please contact your system administrator at:') }}
                <br>
                <span class="font-mono font-semibold text-blue-700 bg-blue-50 px-2 py-1 rounded inline-block mt-2">admin@perusahaan.com</span>
            </p>
            <button 
                type="button" 
                @click="requestAccessModal = false"
                class="w-full h-11 min-h-[44px] py-2 px-4 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-sm transition-colors cursor-pointer"
            >
                {{ __('Close') }}
            </button>
        </div>
    </div>

    <!-- Footer -->
    <footer class="w-full py-4 px-6 text-center text-xs text-slate-400 border-t border-slate-200/80 bg-white/70">
        Solar Tracker &copy; {{ date('Y') }} Industrial Solar Monitoring Pilot &bull; Dual-Axis PV Platform
    </footer>
</body>
</html>
