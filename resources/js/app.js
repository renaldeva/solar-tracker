import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import './chart-theme';

window.Alpine = Alpine;
window.Chart = Chart;

// Global translations dictionary
window.trans = window.trans || {};
window.__ = function (key, replacements = {}) {
    let translation = window.trans[key] || key;
    for (const [placeholder, value] of Object.entries(replacements)) {
        translation = translation.replace(new RegExp(`:${placeholder}`, 'g'), value);
    }
    return translation;
};

// Power formatting in Watt (W) only, never kW
// Round to whole number when >= 100 W, otherwise 1 decimal
// Indonesian format (dot for thousands, comma for decimals)
window.formatPowerW = function (watts, withUnit = true) {
    if (watts === null || watts === undefined || isNaN(watts)) {
        return withUnit ? '-- W' : '--';
    }
    const val = Number(watts);
    const abs = Math.abs(val);
    const precision = abs >= 100 ? 0 : 1;
    const rounded = precision === 0 ? Math.round(val) : val;

    const formatted = new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: precision,
        maximumFractionDigits: precision,
    }).format(rounded);

    return withUnit ? `${formatted} W` : formatted;
};

// Generic Indonesian number formatter
window.formatNumberId = function (val, decimals = 1) {
    if (val === null || val === undefined || isNaN(val)) {
        return '--';
    }
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    }).format(Number(val));
};

// Global helper for WIB live clock (Indonesian format)
window.wibClock = function () {
    return {
        timeString: '',
        dateString: '',
        init() {
            this.update();
            setInterval(() => this.update(), 1000);
        },
        update() {
            const now = new Date();
            // WIB is UTC+7
            const formatterTime = new Intl.DateTimeFormat('id-ID', {
                timeZone: 'Asia/Jakarta',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
            });
            const formatterDate = new Intl.DateTimeFormat('id-ID', {
                timeZone: 'Asia/Jakarta',
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            });
            // Replace colons with dots per standard Indonesian time punctuation (e.g. 22.09.15 WIB)
            this.timeString = formatterTime.format(now).replace(/:/g, '.') + ' WIB';
            this.dateString = formatterDate.format(now);
        },
    };
};

// Global Theme Manager (Light, Dark, System)
window.themeManager = {
    theme: 'system', // 'light' | 'dark' | 'system'
    isDark: false,
    open: false,
    init() {
        try {
            this.theme = localStorage.getItem('theme') || 'system';
        } catch (e) {
            this.theme = 'system';
        }
        this.apply();

        // Listen for OS system theme changes
        if (window.matchMedia) {
            const media = window.matchMedia('(prefers-color-scheme: dark)');
            const listener = () => {
                if (this.theme === 'system') {
                    this.apply();
                }
            };
            if (media.addEventListener) {
                media.addEventListener('change', listener);
            } else if (media.addListener) {
                media.addListener(listener);
            }
        }
    },
    setTheme(val) {
        this.theme = val;
        try {
            localStorage.setItem('theme', val);
        } catch (e) {}
        this.apply();
        this.open = false;
    },
    apply() {
        let dark = false;
        if (this.theme === 'dark') {
            dark = true;
        } else if (this.theme === 'light') {
            dark = false;
        } else {
            dark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
        this.isDark = dark;
        if (dark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        window.dispatchEvent(new CustomEvent('theme-changed', {
            detail: { theme: this.theme, isDark: dark }
        }));
    }
};

// Initialize theme manager immediately
window.themeManager.init();

// Alpine component for theme dropdown
window.themeDropdown = function () {
    return {
        get open() { return window.themeManager.open; },
        set open(val) { window.themeManager.open = val; },
        get theme() { return window.themeManager.theme; },
        get isDark() { return window.themeManager.isDark; },
        setTheme(val) {
            window.themeManager.setTheme(val);
        }
    };
};

Alpine.start();
