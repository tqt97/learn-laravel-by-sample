window.LabChart = (() => {
    let chart = null;

    function renderComparisonChart(data) {
        const ctx = document.getElementById('lab-chart');

        if (!ctx) return;

        const chartConfig = data.scenario?.ui?.chart || {};

        const metricKey = chartConfig.metric_key || 'result_count';
        const limitKey = chartConfig.limit_key || 'valid_limit';

        const naiveResult = data.naive.metrics[metricKey] ?? 0;
        const productionResult = data.production.metrics[metricKey] ?? 0;

        const naiveLimit = data.naive.metrics[limitKey] ?? 1;
        const productionLimit = data.production.metrics[limitKey] ?? 1;

        const chartData = {
            labels: [
                chartConfig.naive_label || 'Naive Result',
                chartConfig.production_label || 'Production Result',
                chartConfig.limit_label || 'Valid Limit',
            ],
            datasets: [{
                label: 'Count',
                data: [
                    naiveResult,
                    productionResult,
                    Math.max(naiveLimit, productionLimit),
                ],
            }],
        };

        if (chart) {
            chart.data = chartData;
            chart.update();
            return;
        }

        chart = new Chart(ctx, {
            type: 'bar',
            data: chartData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false,
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                        },
                    },
                },
            },
        });
    }

    return {
        renderComparisonChart,
    };
})();
