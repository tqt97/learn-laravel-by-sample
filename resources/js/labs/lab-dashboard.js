(() => {
    let chart = null;

    function scenario() {
        return document.getElementById('scenario-select').value;
    }

    async function refreshState() {
        const data = await LabApi.state(scenario());

        LabUi.renderMetrics('naive', data.naive.metrics);
        LabUi.renderInvariants('naive', data.naive.invariants);

        LabUi.renderMetrics('production', data.production.metrics);
        LabUi.renderInvariants('production', data.production.invariants);

        renderChart(data);
    }

    async function run(mode, count) {
        LabUi.log(mode, `Running ${count} request(s)...`);

        const requests = [];

        for (let i = 0; i < count; i++) {
            requests.push(
                LabApi.action(scenario(), mode, {
                    request_key: crypto.randomUUID(),
                    delay_microseconds: 300000,
                })
                    .then(response => {
                        LabUi.log(mode, response.message, 'success');
                    })
                    .catch(error => {
                        LabUi.log(mode, error.message || 'Request failed.', 'error');
                    })
            );
        }

        await Promise.allSettled(requests);

        await refreshState();
    }

    async function reset(mode) {
        const response = await LabApi.reset(scenario(), mode);

        LabUi.clearLog(mode);
        LabUi.log(mode, response.message, 'info');

        await refreshState();
    }

    async function resetAll() {
        const response = await LabApi.resetAll(scenario());

        LabUi.clearLog('naive');
        LabUi.clearLog('production');

        LabUi.log('naive', response.message, 'info');
        LabUi.log('production', response.message, 'info');

        await refreshState();
    }

    function renderChart(data) {
        const naiveOrders = data.naive.metrics.orders_count ?? 0;
        const productionOrders = data.production.metrics.orders_count ?? 0;

        const naiveLimit = data.naive.metrics.valid_stock_limit ?? 1;
        const productionLimit = data.production.metrics.valid_stock_limit ?? 1;

        const ctx = document.getElementById('lab-chart');

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

    document.addEventListener('click', event => {
        const runButton = event.target.closest('[data-run]');
        const resetButton = event.target.closest('[data-reset]');
        const resetAllButton = event.target.closest('[data-reset-all]');

        if (runButton) {
            run(
                runButton.getAttribute('mode'),
                Number(runButton.getAttribute('count')),
            );
        }

        if (resetButton) {
            reset(resetButton.getAttribute('mode'));
        }

        if (resetAllButton) {
            resetAll();
        }
    });

    document
        .getElementById('scenario-select')
        .addEventListener('change', refreshState);

    refreshState();
})();
