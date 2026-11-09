<!DOCTYPE html>
<html lang="{{ app()->getLocale() == 'en' ? 'en' : 'id' }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Set New Password') }} – {{ config('app.name', 'Solar Tracker') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased bg-slate-50 text-slate-900 flex flex-col justify-between">
    <header class="w-full px-6 py-4 flex items-center justify-between border-b border-slate-200/80 bg-white/70">
        <a href="{{ route('login') }}" class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <span class="text-base font-bold text-slate-900">Solar Tracker</span>
        </a>
    </header>

    <main class="flex-1 flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md bg-white rounded-2xl border border-slate-200/80 shadow-md p-8 sm:p-10">
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-slate-900">{{ __('Set New Password') }}</h2>
                <p class="text-sm text-slate-500 mt-1.5">{{ __('Choose a secure password for your account.') }}</p>
            </div>

            @if ($errors->any())
                <div class="mb-4 p-3.5 rounded-lg bg-rose-50 border border-rose-200 text-sm text-rose-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-700 mb-1.5">{{ __('Work Email') }}</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autofocus
                        class="w-full px-4 py-2.5 h-11 min-h-[44px] text-base rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-700 mb-1.5">{{ __('New Password') }}</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password"
                        class="w-full px-4 py-2.5 h-11 min-h-[44px] text-base rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-slate-700 mb-1.5">{{ __('Confirm Password') }}</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                        class="w-full px-4 py-2.5 h-11 min-h-[44px] text-base rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <button type="submit" class="w-full h-11 min-h-[44px] py-2.5 px-4 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-base font-semibold shadow-xs transition-colors cursor-pointer">
                    {{ __('Reset Password') }}
                </button>
            </form>
        </div>
    </main>

    <footer class="w-full py-4 px-6 text-center text-xs text-slate-400 border-t border-slate-200/80 bg-white/70">
        Solar Tracker &copy; {{ date('Y') }}
    </footer>
</body>
</html>
