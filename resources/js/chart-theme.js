import Chart from 'chart.js/auto';

/**
 * Helper to read CSS variables with a fallback
 */
function getCssVar(name, fallback) {
    if (typeof window === 'undefined') return fallback;
    try {
        const val = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        return val || fallback;
    } catch (e) {
        return fallback;
    }
}

/**
 * Returns dynamic theme colors based on CSS variables
 */
export function getChartThemeColors() {
    const isDark = typeof document !== 'undefined' && document.documentElement.classList.contains('dark');
    return {
        isDark,
        text: getCssVar('--chart-text', isDark ? '#94a3b8' : '#64748b'),
        textHeading: getCssVar('--chart-text-heading', isDark ? '#f1f5f9' : '#0f172a'),
        grid: getCssVar('--chart-grid', isDark ? 'rgba(34, 48, 74, 0.7)' : 'rgba(226, 232, 240, 0.7)'),
        tooltipBg: getCssVar('--chart-tooltip-bg', isDark ? '#1e293b' : '#0f172a'),
        tooltipText: getCssVar('--chart-tooltip-text', isDark ? '#f1f5f9' : '#ffffff'),
        tooltipBorder: getCssVar('--chart-tooltip-border', isDark ? '#334155' : '#e2e8f0'),
        barOld: getCssVar('--chart-bar-old', isDark ? '#1e3a5f' : '#dbeafe'),
        barNew: getCssVar('--chart-bar-new', isDark ? '#60a5fa' : '#2563eb'),
        primary: isDark ? '#60a5fa' : '#2563eb',
        primaryFill: isDark ? 'rgba(96, 165, 250, 0.15)' : 'rgba(37, 99, 235, 0.08)',
        gaugeBg: getCssVar('--gauge-bg', isDark ? '#1e293b' : '#e2e8f0'),
        gaugeFill: getCssVar('--gauge-fill', isDark ? '#60a5fa' : '#2563eb'),
    };
}

if (typeof window !== 'undefined') {
    window.getChartThemeColors = getChartThemeColors;
}

/**
 * Apply Chart.js global defaults using dynamic CSS variables
 */
export function applyChartTheme(ChartInstance = Chart) {
    if (!ChartInstance || !ChartInstance.defaults) {
        return;
    }

    const colors = getChartThemeColors();
    const d = ChartInstance.defaults;

    // Typography
    d.font.family = "'JetBrains Mono', 'Inter', monospace";
    d.font.size = 11;
    d.color = colors.text;

    // Responsive & Layout
    d.responsive = true;
    d.maintainAspectRatio = false;

    // Elements
    if (!d.elements) d.elements = {};
    if (!d.elements.line) d.elements.line = {};
    if (!d.elements.point) d.elements.point = {};
    if (!d.elements.bar) d.elements.bar = {};

    d.elements.line.borderWidth = 2.5;
    d.elements.line.tension = 0.35;
    d.elements.line.fill = false;

    d.elements.point.radius = 0;
    d.elements.point.hoverRadius = 5;
    d.elements.point.hitRadius = 10;
    d.elements.point.borderWidth = 2;
    d.elements.point.backgroundColor = colors.isDark ? '#111a2e' : '#ffffff';

    d.elements.bar.borderRadius = 6;
    d.elements.bar.borderWidth = 0;

    // Scales
    if (!d.scales) d.scales = {};
    const applyScaleDefaults = (scale) => {
        if (!scale) return;
        scale.grid = scale.grid || {};
        scale.ticks = scale.ticks || {};
        scale.border = scale.border || {};

        scale.grid.color = colors.grid;
        scale.grid.drawBorder = false;
        scale.border.display = false;
        scale.ticks.color = colors.text;
        scale.ticks.font = {
            family: "'JetBrains Mono', monospace",
            size: 11,
        };
    };

    if (d.scale) applyScaleDefaults(d.scale);

    // Plugins: Tooltip
    if (!d.plugins) d.plugins = {};
    if (!d.plugins.tooltip) d.plugins.tooltip = {};
    d.plugins.tooltip.enabled = true;
    d.plugins.tooltip.backgroundColor = colors.tooltipBg;
    d.plugins.tooltip.titleColor = colors.tooltipText;
    d.plugins.tooltip.bodyColor = colors.tooltipText;
    d.plugins.tooltip.borderColor = colors.tooltipBorder;
    d.plugins.tooltip.borderWidth = 1;
    d.plugins.tooltip.padding = 10;
    d.plugins.tooltip.cornerRadius = 8;
    d.plugins.tooltip.displayColors = true;
    d.plugins.tooltip.boxWidth = 8;
    d.plugins.tooltip.boxHeight = 8;
    d.plugins.tooltip.boxPadding = 4;
    d.plugins.tooltip.titleFont = {
        family: "'Inter', sans-serif",
        size: 12,
        weight: '600',
    };
    d.plugins.tooltip.bodyFont = {
        family: "'JetBrains Mono', monospace",
        size: 12,
    };

    // Plugins: Legend
    if (!d.plugins.legend) d.plugins.legend = {};
    d.plugins.legend.display = true;
    if (!d.plugins.legend.labels) d.plugins.legend.labels = {};
    d.plugins.legend.labels.color = colors.textHeading;
    d.plugins.legend.labels.usePointStyle = true;
    d.plugins.legend.labels.pointStyle = 'circle';
    d.plugins.legend.labels.boxWidth = 8;
    d.plugins.legend.labels.boxHeight = 8;
    d.plugins.legend.labels.padding = 16;
    d.plugins.legend.labels.font = {
        family: "'Inter', sans-serif",
        size: 12,
        weight: '500',
    };
}

/**
 * Update all active Chart.js instances live on theme change
 */
export function updateAllChartsOnThemeChange(ChartInstance = Chart) {
    if (!ChartInstance) return;
    applyChartTheme(ChartInstance);
    const colors = getChartThemeColors();

    const instances = ChartInstance.instances || {};
    Object.values(instances).forEach((chart) => {
        if (!chart || !chart.options) return;

        // 1. Update scales grid & ticks
        if (chart.options.scales) {
            Object.values(chart.options.scales).forEach((scale) => {
                if (!scale) return;
                if (scale.grid) {
                    scale.grid.color = colors.grid;
                }
                if (scale.ticks) {
                    scale.ticks.color = colors.text;
                }
                if (scale.title) {
                    scale.title.color = colors.text;
                }
            });
        }

        // 2. Update plugins: legend & tooltip
        if (chart.options.plugins) {
            if (chart.options.plugins.legend && chart.options.plugins.legend.labels) {
                chart.options.plugins.legend.labels.color = colors.textHeading;
            }
            if (chart.options.plugins.tooltip) {
                chart.options.plugins.tooltip.backgroundColor = colors.tooltipBg;
                chart.options.plugins.tooltip.titleColor = colors.tooltipText;
                chart.options.plugins.tooltip.bodyColor = colors.tooltipText;
                chart.options.plugins.tooltip.borderColor = colors.tooltipBorder;
            }
        }

        // 3. Update dataset primary colors & bar colors
        if (chart.data && chart.data.datasets) {
            chart.data.datasets.forEach((ds) => {
                // If it's a primary blue line chart (dashboard / live power)
                if (ds.borderColor === '#2563eb' || ds.borderColor === '#60a5fa') {
                    ds.borderColor = colors.primary;
                    if (ds.backgroundColor && typeof ds.backgroundColor === 'string' && ds.backgroundColor.includes('rgba(37, 99, 235')) {
                        ds.backgroundColor = colors.primaryFill;
                    } else if (ds.backgroundColor && typeof ds.backgroundColor === 'string' && ds.backgroundColor.includes('rgba(96, 165, 250')) {
                        ds.backgroundColor = colors.primaryFill;
                    }
                    if (ds.pointBackgroundColor) {
                        ds.pointBackgroundColor = colors.primary;
                    }
                    if (ds.pointBorderColor) {
                        ds.pointBorderColor = colors.isDark ? '#111a2e' : '#ffffff';
                    }
                }

                // If it's the comparison bar chart
                if (ds.label && ds.label.includes('Kemarin')) {
                    ds.backgroundColor = colors.barOld;
                } else if (ds.label && ds.label.includes('Hari Ini') && !ds.label.includes('Kemarin')) {
                    ds.backgroundColor = colors.barNew;
                }
            });
        }

        try {
            chart.update('none'); // smooth immediate update without replay animation
        } catch (e) {
            console.debug('Chart update error:', e);
        }
    });
}

// Global hook for live theme switching
if (typeof window !== 'undefined') {
    window.applyChartTheme = applyChartTheme;
    window.updateAllChartsOnThemeChange = updateAllChartsOnThemeChange;

    window.addEventListener('theme-changed', () => {
        updateAllChartsOnThemeChange(Chart);
    });
}

// Auto apply initial defaults
applyChartTheme(Chart);

export default applyChartTheme;
