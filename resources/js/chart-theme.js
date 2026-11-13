import Chart from 'chart.js/auto';

/**
 * Smart Nursery Light Theme defaults for Chart.js
 * - Clean light aesthetic
 * - Transparent background
 * - Very subtle grid (#f1f5f9 / rgba(226, 232, 240, 0.6))
 * - Blue palette: #2563eb (newest/primary), #93c5fd / #dbeafe (older/history)
 * - Rounded bars, smooth lines, points hidden until hover
 * - Pill-shaped legend
 */
export function applyChartTheme(ChartInstance = Chart) {
    if (!ChartInstance || !ChartInstance.defaults) {
        return;
    }

    const d = ChartInstance.defaults;

    // Typography
    d.font.family = "'JetBrains Mono', 'Inter', monospace";
    d.font.size = 11;
    d.color = '#64748b'; // muted secondary text

    // Responsive & Layout
    d.responsive = true;
    d.maintainAspectRatio = false;

    // Elements: Line & Point
    if (!d.elements) d.elements = {};
    if (!d.elements.line) d.elements.line = {};
    if (!d.elements.point) d.elements.point = {};
    if (!d.elements.bar) d.elements.bar = {};

    d.elements.line.borderWidth = 2.5;
    d.elements.line.tension = 0.35;
    d.elements.line.fill = false;

    // Points hidden by default, visible on hover
    d.elements.point.radius = 0;
    d.elements.point.hoverRadius = 5;
    d.elements.point.hitRadius = 10;
    d.elements.point.borderWidth = 2;
    d.elements.point.backgroundColor = '#ffffff';

    // Bar elements
    d.elements.bar.borderRadius = 6;
    d.elements.bar.borderWidth = 0;

    // Scales (Grid & Axes)
    if (!d.scales) d.scales = {};

    const applyScaleDefaults = (scale) => {
        if (!scale) return;
        scale.grid = scale.grid || {};
        scale.ticks = scale.ticks || {};
        scale.border = scale.border || {};

        scale.grid.color = 'rgba(226, 232, 240, 0.7)'; // soft border line
        scale.grid.drawBorder = false;
        scale.border.display = false;
        scale.ticks.color = '#64748b';
        scale.ticks.font = {
            family: "'JetBrains Mono', monospace",
            size: 11,
        };
    };

    if (d.scale) applyScaleDefaults(d.scale);

    // Plugins: Tooltip, Legend
    if (!d.plugins) d.plugins = {};

    // Tooltip - Clean rounded dark contrast or crisp light
    if (!d.plugins.tooltip) d.plugins.tooltip = {};
    d.plugins.tooltip.enabled = true;
    d.plugins.tooltip.backgroundColor = '#0f172a'; // crisp dark tooltip for contrast
    d.plugins.tooltip.titleColor = '#ffffff';
    d.plugins.tooltip.bodyColor = '#f8fafc';
    d.plugins.tooltip.borderColor = '#e2e8f0';
    d.plugins.tooltip.borderWidth = 0;
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

    // Legend - pill style
    if (!d.plugins.legend) d.plugins.legend = {};
    d.plugins.legend.display = true;
    if (!d.plugins.legend.labels) d.plugins.legend.labels = {};
    d.plugins.legend.labels.color = '#0f172a';
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
