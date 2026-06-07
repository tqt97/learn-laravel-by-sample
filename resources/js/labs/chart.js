window.LabChart = (() => {
    let chart = null;

    function renderComparisonChart(data) {
        const ctx = document.getElementById('lab-chart');

        if (!ctx) {
            return;
        }

        const naiveOrders = data.naive.metrics.orders_count ?? 0;
        const productionOrders = data.production.metrics.orders_count ?? 0;

        const naiveLimit = data.naive.metrics.valid_stock_limit ?? 1;
        const productionLimit = data.production.metrics.valid_stock_limit ?? 1;

        const chartData = {
            labels: ['Naive Orders', 'Production Orders', 'Valid Stock Limit'],
            datasets: [{
                label: 'Count',
                data: [
                    naiveOrders,
                    productionOrders,
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
