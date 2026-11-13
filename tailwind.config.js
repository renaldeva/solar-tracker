/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                bg: 'var(--theme-bg)',
                panel: 'var(--theme-panel)',
                'panel-alt': 'var(--theme-panel-alt)',
                line: 'var(--theme-line)',
                ink: 'var(--theme-ink)',
                muted: 'var(--theme-muted)',
                accent: {
                    DEFAULT: 'var(--theme-accent)',
                    mid: 'var(--theme-accent-mid)',
                    light: 'var(--theme-accent-light)',
                },
                ok: {
                    DEFAULT: 'var(--theme-ok)',
                    light: 'var(--theme-ok-light)',
                },
                warn: {
                    DEFAULT: 'var(--theme-warn)',
                    light: 'var(--theme-warn-light)',
                },
                danger: {
                    DEFAULT: 'var(--theme-danger)',
                    light: 'var(--theme-danger-light)',
                },
            },
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'sans-serif'],
                mono: ['"JetBrains Mono"', 'ui-monospace', 'SFMono-Regular', 'Menlo', 'Monaco', 'Consolas', 'monospace'],
            },
            borderRadius: {
                '2xl': '1rem',
                '3xl': '1.5rem',
            },
        },
    },
    plugins: [],
};
