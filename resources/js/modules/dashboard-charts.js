import Chart from 'chart.js/auto'

const palette = {
    brand: '#0f766e',
    brandSoft: 'rgba(15, 118, 110, 0.15)',
    sky: '#0284c7',
    skySoft: 'rgba(2, 132, 199, 0.15)',
    amber: '#d97706',
    amberSoft: 'rgba(217, 119, 6, 0.15)',
    slate: '#64748b',
}

function readJson(element, attribute, fallback) {
    const raw = element.getAttribute(attribute)

    if (!raw) {
        return fallback
    }

    try {
        return JSON.parse(raw)
    } catch {
        console.warn(`[chart] could not parse ${attribute}`)
        return fallback
    }
}

function axisOptions() {
    return {
        grid: { color: 'rgba(148, 163, 184, 0.2)' },
        ticks: { color: '#64748b', font: { size: 11 } },
    }
}

function initLineChart(element) {
    const payload = readJson(element, 'data-series', { labels: [], values: [] })

    if (!payload.labels?.length) {
        return
    }

    new Chart(element, {
        type: 'line',
        data: {
            labels: payload.labels,
            datasets: [
                {
                    label: payload.label ?? 'Volume',
                    data: payload.values,
                    borderColor: palette.brand,
                    backgroundColor: palette.brandSoft,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 2,
                    pointHoverRadius: 4,
                    borderWidth: 2,
                },
                ...(payload.comparison?.length
                    ? [
                          {
                              label: payload.comparisonLabel ?? 'Previous period',
                              data: payload.comparison,
                              borderColor: palette.slate,
                              borderDash: [6, 4],
                              fill: false,
                              tension: 0.35,
                              pointRadius: 0,
                              borderWidth: 1.5,
                          },
                      ]
                    : []),
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { labels: { boxWidth: 12, font: { size: 11 }, color: '#475569' } } },
            scales: { y: { beginAtZero: true, ...axisOptions() }, x: axisOptions() },
        },
    })
}

function initBarChart(element) {
    const payload = readJson(element, 'data-series', { labels: [], values: [] })

    if (!payload.labels?.length) {
        return
    }

    new Chart(element, {
        type: 'bar',
        data: {
            labels: payload.labels,
            datasets: [
                {
                    label: payload.label ?? 'Litres',
                    data: payload.values,
                    backgroundColor: payload.volatile ? palette.amberSoft : palette.sky,
                    borderColor: payload.volatile ? palette.amber : palette.sky,
                    borderWidth: 1,
                    borderRadius: 4,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: (context) => `${context.parsed.y} ${payload.unit ?? 'L'}` } },
            },
            scales: { y: { beginAtZero: true, ...axisOptions() }, x: axisOptions() },
        },
    })
}

function initDoughnutChart(element) {
    const payload = readJson(element, 'data-series', { labels: [], values: [] })

    if (!payload.labels?.length) {
        return
    }

    const colours = [palette.brand, palette.sky, palette.amber, palette.slate, '#7c3aed', '#be123c']

    new Chart(element, {
        type: 'doughnut',
        data: {
            labels: payload.labels,
            datasets: [
                {
                    data: payload.values,
                    backgroundColor: payload.labels.map((_, index) => colours[index % colours.length]),
                    borderWidth: 0,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '62%',
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 }, color: '#475569' } },
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            const total = context.dataset.data.reduce((sum, value) => sum + value, 0) || 1
                            const share = ((context.parsed / total) * 100).toFixed(1)
                            return `${context.label}: ${context.parsed.toLocaleString()} (${share}%)`
                        },
                    },
                },
            },
        },
    })
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-chart="line"]').forEach(initLineChart)
    document.querySelectorAll('[data-chart="bar"]').forEach(initBarChart)
    document.querySelectorAll('[data-chart="doughnut"]').forEach(initDoughnutChart)
})
