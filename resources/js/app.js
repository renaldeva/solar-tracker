import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

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

// Set Chart.js defaults for typography and readability
if (Chart && Chart.defaults) {
    Chart.defaults.font.family = "'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif";
    Chart.defaults.font.size = 13;
    Chart.defaults.color = '#475569';
    if (Chart.defaults.plugins) {
        if (Chart.defaults.plugins.legend && Chart.defaults.plugins.legend.labels) {
            Chart.defaults.plugins.legend.labels.font = { size: 14, family: "'Inter', sans-serif" };
            Chart.defaults.plugins.legend.labels.color = '#334155';
        }
        if (Chart.defaults.plugins.tooltip) {
            Chart.defaults.plugins.tooltip.titleFont = { size: 13, weight: '600' };
            Chart.defaults.plugins.tooltip.bodyFont = { size: 13 };
            Chart.defaults.plugins.tooltip.padding = 10;
        }
    }
}

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

Alpine.start();
