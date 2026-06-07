(() => {
    function getSelectedScenarioKey() {
        return document.getElementById('scenario-select')?.value;
    }

    async function refreshScenarioState() {
        const scenarioKey = getSelectedScenarioKey();

        if (!scenarioKey) {
            return;
        }

        const data = await LabApi.state(scenarioKey);

        LabScenarioMeta.renderScenarioMeta(data.scenario);

        LabLearningCenter.setLearningCenterState(data.scenario.learning_center || {});
        LabLearningCenter.renderLearningCenterTab(
            LabLearningCenter.getActiveLearningTab()
        );

        LabUi.renderMetrics('naive', data.naive.metrics);
        LabUi.renderInvariants('naive', data.naive.invariants);

        LabUi.renderMetrics('production', data.production.metrics);
        LabUi.renderInvariants('production', data.production.invariants);

        LabChart.renderComparisonChart(data);
    }

    async function runScenarioAction(mode, count, runMode = 'single') {
        const label = runMode === 'batch_race'
            ? `Simulating ${count} reader(s)...`
            : `Running ${count} real request(s)...`;

        LabUi.log(mode, label);

        // Real requests are actual browser Ajax requests.
        // The result may depend on web server and PHP worker concurrency.
        if (runMode === 'single') {
            await runRealRequests(mode, count);
            await refreshScenarioState();

            return;
        }

        // Batch race is a deterministic simulation for local learning.
        // It makes race-condition bugs visible even when local PHP handles requests sequentially.
        await runBatchRaceSimulation(mode, count);
        await refreshScenarioState();
    }

    async function runRealRequests(mode, count) {
        const requests = [];

        for (let i = 0; i < count; i++) {
            requests.push(
                LabApi.action(getSelectedScenarioKey(), mode, {
                    run_mode: 'single',
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
    }

    async function runBatchRaceSimulation(mode, count) {
        await LabApi.action(getSelectedScenarioKey(), mode, {
            run_mode: 'batch_race',
            count,
            delay_microseconds: 300000,
        })
            .then(response => {
                LabUi.log(mode, response.message, 'success');
            })
            .catch(error => {
                LabUi.log(mode, error.message || 'Request failed.', 'error');
            });
    }

    async function resetScenarioMode(mode) {
        const response = await LabApi.reset(getSelectedScenarioKey(), mode);

        LabUi.clearLog(mode);
        LabUi.log(mode, response.message, 'info');

        await refreshScenarioState();
    }

    async function resetScenarioAllModes() {
        const response = await LabApi.resetAll(getSelectedScenarioKey());

        LabUi.clearLog('naive');
        LabUi.clearLog('production');

        LabUi.log('naive', response.message, 'info');
        LabUi.log('production', response.message, 'info');

        await refreshScenarioState();
    }

    function bindEvents() {
        document.addEventListener('click', event => {
            const learningTab = event.target.closest('[data-learning-tab]');

            if (learningTab) {
                LabLearningCenter.renderLearningCenterTab(
                    learningTab.getAttribute('data-learning-tab')
                );

                return;
            }

            const copyButton = event.target.closest('[data-copy-code]');

            if (copyButton) {
                LabLearningCenter.handleCopyCode(copyButton);
                return;
            }

            const runButton = event.target.closest('[data-run]');

            if (runButton) {
                runScenarioAction(
                    runButton.getAttribute('mode'),
                    Number(runButton.getAttribute('count')),
                    runButton.getAttribute('run-mode') || 'single',
                );

                return;
            }

            const customRunButton = event.target.closest('[data-run-custom]');

            if (customRunButton) {
                handleCustomRun(customRunButton);
                return;
            }

            const resetButton = event.target.closest('[data-reset]');

            if (resetButton) {
                resetScenarioMode(resetButton.getAttribute('mode'));
                return;
            }

            const resetAllButton = event.target.closest('[data-reset-all]');

            if (resetAllButton) {
                resetScenarioAllModes();
            }
        });

        document
            .getElementById('scenario-select')
            ?.addEventListener('change', refreshScenarioState);
    }

    function handleCustomRun(button) {
        const inputTarget = button.getAttribute('input-target');
        const input = document.querySelector(`[data-custom-count="${inputTarget}"]`);

        const min = Number(input?.getAttribute('min') || 1);
        const max = Number(input?.getAttribute('max') || 100);
        const count = LabUtils.clampNumber(input?.value, min, max);

        if (input) {
            input.value = count;
        }

        runScenarioAction(
            button.getAttribute('mode'),
            count,
            button.getAttribute('run-mode') || 'single',
        );
    }

    function initializeLearningCenterFromBlade() {
        const container = document.getElementById('learning-center-content');

        if (!container?.dataset.learningCenter) {
            return;
        }

        try {
            LabLearningCenter.setLearningCenterState(
                JSON.parse(container.dataset.learningCenter || '{}')
            );

            LabLearningCenter.renderLearningCenterTab('overview');
        } catch (error) {
            console.error('Invalid learning center JSON.', error);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        initializeLearningCenterFromBlade();
        bindEvents();
        refreshScenarioState();
    });
})();
