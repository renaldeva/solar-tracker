<!DOCTYPE html>
<html lang="{{ app()->getLocale() == 'en' ? 'en' : 'id' }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Password Recovery') }} – {{ config('app.name', 'Solar Tracker') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased bg-slate-50 text-ink flex flex-col justify-between">
    <header class="w-full px-6 py-4 flex items-center justify-between border-b border-line bg-white shadow-2xs">
        <a href="{{ route('login') }}" class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center text-white shadow-xs">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <span class="text-sm font-bold text-ink tracking-tight">Solar Tracker</span>
        </a>
    </header>

    <main class="flex-1 flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md bg-white rounded-2xl border border-line p-7 sm:p-9 shadow-xs">
            <div class="text-center mb-6">
                <div class="inline-flex w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 items-center justify-center mb-3.5 shadow-2xs">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-ink">{{ __('Password Recovery') }}</h2>
                <p class="text-xs text-muted mt-1">{{ __('Enter your account email to receive a password reset link.') }}</p>
            </div>

            @if (session('status'))
                <div class="mb-4 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Work Email') }}</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full px-3.5 py-2.5 h-11 text-sm rounded-xl bg-white border border-line text-ink placeholder-muted focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 shadow-2xs">
                </div>

                <button type="submit" class="w-full h-11 py-2.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition-colors shadow-xs cursor-pointer">
                    {{ __('Email Password Reset Link') }}
                </button>

                <div class="pt-2 text-center">
                    <a href="{{ route('login') }}" class="text-xs text-blue-600 hover:underline font-medium">
                        &larr; {{ __('Back to sign in') }}
                    </a>
                </div>
            </form>
        </div>
    </main>

    <footer class="w-full py-4 px-6 text-center text-xs text-muted border-t border-line bg-white font-mono">
        Solar Tracker &copy; {{ date('Y') }} Industrial Solar Monitoring Pilot
    </footer>
</body>
</html>
