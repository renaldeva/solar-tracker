<!DOCTYPE html>
<html lang="{{ app()->getLocale() == 'en' ? 'en' : 'id' }}" class="h-full bg-bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Set New Password') }} – {{ config('app.name', 'Solar Tracker') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased bg-bg text-ink flex flex-col justify-between">
    <header class="w-full px-6 py-3.5 flex items-center justify-between border-b border-line bg-panel">
        <a href="{{ route('login') }}" class="flex items-center space-x-3">
            <div class="w-8 h-8 rounded-md bg-accent flex items-center justify-center text-ink">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <span class="text-sm font-semibold text-ink">Solar Tracker</span>
        </a>
    </header>

    <main class="flex-1 flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-md bg-panel rounded-md border border-line p-6 sm:p-8">
            <div class="text-center mb-6">
                <h2 class="text-lg font-semibold text-ink">{{ __('Set New Password') }}</h2>
                <p class="text-xs text-muted mt-1">{{ __('Choose a secure password for your account.') }}</p>
            </div>

            @if ($errors->any())
                <div class="mb-4 p-3 rounded-md bg-danger/10 border border-danger/30 text-xs text-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="email" class="block text-xs font-semibold text-muted uppercase tracking-wider mb-1">{{ __('Work Email') }}</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autofocus
                        class="w-full px-3 py-2 h-10 text-sm rounded-md bg-bg border border-line text-ink placeholder-muted focus:outline-none focus:border-accent">
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold text-muted uppercase tracking-wider mb-1">{{ __('New Password') }}</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password"
                        class="w-full px-3 py-2 h-10 text-sm rounded-md bg-bg border border-line text-ink placeholder-muted focus:outline-none focus:border-accent">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold text-muted uppercase tracking-wider mb-1">{{ __('Confirm Password') }}</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                        class="w-full px-3 py-2 h-10 text-sm rounded-md bg-bg border border-line text-ink placeholder-muted focus:outline-none focus:border-accent">
                </div>

                <button type="submit" class="w-full h-10 py-2 px-4 rounded-md bg-accent hover:bg-blue-600 text-ink text-sm font-medium transition-colors cursor-pointer">
                    {{ __('Reset Password') }}
                </button>
            </form>
        </div>
    </main>

    <footer class="w-full py-3 px-6 text-center text-xs text-muted border-t border-line bg-panel font-mono">
        Solar Tracker &copy; {{ date('Y') }}
    </footer>
</body>
</html>
