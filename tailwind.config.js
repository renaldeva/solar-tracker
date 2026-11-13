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
                bg: '#f8fafc',
                panel: '#ffffff',
                'panel-alt': '#f1f5f9',
                line: '#e5e7eb',
                ink: '#0f172a',
                muted: '#64748b',
                accent: {
                    DEFAULT: '#2563eb',
                    mid: '#dbeafe',
                    light: '#eff6ff',
                },
                ok: {
                    DEFAULT: '#10b981',
                    light: '#ecfdf5',
                },
                warn: {
                    DEFAULT: '#f59e0b',
                    light: '#fffbeb',
                },
                danger: {
                    DEFAULT: '#ef4444',
                    light: '#fef2f2',
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
