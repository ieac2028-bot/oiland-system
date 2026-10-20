import Chart from 'chart.js/auto'

document.querySelectorAll('[data-timekeeping-chart]').forEach((canvas) => {
    const raw = canvas.getAttribute('data-series')
    if (!raw) return

    let series
    try {
        series = JSON.parse(raw)
    } catch {
        return
    }

    if (!series.labels?.length) return

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: series.labels,
            datasets: [{
                label: 'Employees with time-in',
                data: series.present ?? [],
                backgroundColor: 'rgba(15, 118, 110, 0.72)',
                borderRadius: 6,
                maxBarThickness: 40,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(148, 163, 184, 0.2)' } },
                x: { grid: { display: false } },
            },
        },
    })
})
