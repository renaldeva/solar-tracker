import Chart from 'chart.js/auto';

/**
 * Grafana-style dark theme defaults for Chart.js
 * - Background transparan
 * - Grid halus (#374151)
 * - Font JetBrains Mono untuk angka/ticks
 * - Garis 2px
 * - Titik tersembunyi (muncul saat hover)
 */
export function applyChartTheme(ChartInstance = Chart) {
    if (!ChartInstance || !ChartInstance.defaults) {
        return;
    }

    const d = ChartInstance.defaults;

    // Typography
    d.font.family = "'JetBrains Mono', monospace";
    d.font.size = 11;
    d.color = '#9ca3af'; // muted

    // Responsive & Layout
    d.responsive = true;
    d.maintainAspectRatio = false;

    // Elements: Line & Point
    if (!d.elements) d.elements = {};
    if (!d.elements.line) d.elements.line = {};
    if (!d.elements.point) d.elements.point = {};
    if (!d.elements.bar) d.elements.bar = {};

    d.elements.line.borderWidth = 2;
    d.elements.line.tension = 0.25;
    d.elements.line.fill = false;

    // Titik tersembunyi, muncul saat hover
    d.elements.point.radius = 0;
    d.elements.point.hoverRadius = 4;
    d.elements.point.hitRadius = 8;
    d.elements.point.borderWidth = 1.5;

    // Bar elements
    d.elements.bar.borderRadius = 4;
    d.elements.bar.borderWidth = 0;

    // Scales (Grid & Axes)
    if (!d.scales) d.scales = {};

    const applyScaleDefaults = (scale) => {
        if (!scale) return;
        scale.grid = scale.grid || {};
        scale.ticks = scale.ticks || {};
        scale.border = scale.border || {};

        scale.grid.color = 'rgba(55, 65, 81, 0.45)'; // subtle line color
        scale.grid.drawBorder = false;
        scale.ticks.color = '#9ca3af';
        scale.ticks.font = {
            family: "'JetBrains Mono', monospace",
            size: 11,
        };
        scale.border.color = '#374151';
        scale.border.width = 1;
    };

    if (d.scale) applyScaleDefaults(d.scale);

    // Plugins: Tooltip, Legend
    if (!d.plugins) d.plugins = {};

    // Tooltip - Grafana dark panel style
    if (!d.plugins.tooltip) d.plugins.tooltip = {};
    d.plugins.tooltip.enabled = true;
    d.plugins.tooltip.backgroundColor = '#1f2937'; // panel
    d.plugins.tooltip.titleColor = '#f9fafb';       // ink
    d.plugins.tooltip.bodyColor = '#f9fafb';        // ink
    d.plugins.tooltip.borderColor = '#374151';      // line
    d.plugins.tooltip.borderWidth = 1;
    d.plugins.tooltip.padding = 10;
    d.plugins.tooltip.cornerRadius = 6;
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

    // Legend
    if (!d.plugins.legend) d.plugins.legend = {};
    d.plugins.legend.display = true;
    if (!d.plugins.legend.labels) d.plugins.legend.labels = {};
    d.plugins.legend.labels.color = '#9ca3af';
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

// Auto-apply on import
applyChartTheme(Chart);

export default applyChartTheme;

