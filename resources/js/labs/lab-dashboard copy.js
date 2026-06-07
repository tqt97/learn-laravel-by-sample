(() => {
    let chart = null;
    let currentLearningCenter = {};
    let currentLearningTab = 'overview';

    function scenario() {
        return document.getElementById('scenario-select').value;
    }

    async function refreshState() {
        const data = await LabApi.state(scenario());

        renderScenarioHeader(data.scenario);

        currentLearningCenter = data.scenario.learning_center || {};
        renderLearningCenter(currentLearningTab);

        LabUi.renderMetrics('naive', data.naive.metrics);
        LabUi.renderInvariants('naive', data.naive.invariants);

        LabUi.renderMetrics('production', data.production.metrics);
        LabUi.renderInvariants('production', data.production.invariants);

        renderChart(data);
    }

    async function run(mode, count, runMode = 'single') {
        const label = runMode === 'batch_race'
            ? `Simulating ${count} reader(s)...`
            : `Running ${count} real request(s)...`;

        LabUi.log(mode, label);

        if (runMode === 'batch_race') {
            await LabApi.action(scenario(), mode, {
                run_mode: 'batch_race',
                count: count,
                delay_microseconds: 300000,
            })
                .then(response => {
                    LabUi.log(mode, response.message, 'success');
                })
                .catch(error => {
                    LabUi.log(mode, error.message || 'Request failed.', 'error');
                });

            await refreshState();

            return;
        }

        const requests = [];

        for (let i = 0; i < count; i++) {
            requests.push(
                LabApi.action(scenario(), mode, {
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

    function renderScenarioHeader(scenario) {
        document.getElementById('scenario-subtitle').innerText =
            scenario.subtitle || '';

        document.getElementById('scenario-title').innerText =
            scenario.title || '';

        document.getElementById('scenario-description').innerText =
            scenario.description || '';

        document.getElementById('scenario-action-hint').innerText =
            scenario.action_hint || '';

        renderList('scenario-learning-goals', scenario.learning_goals || [], 'ul');
        renderList('scenario-how-to-use', scenario.how_to_use || [], 'ol');
        renderTechniqueBadges('naive-techniques', scenario.naive_techniques || [], 'naive');
        renderTechniqueBadges('production-techniques', scenario.production_techniques || [], 'production');
        renderActionPresets(scenario);
    }

    function renderTechniqueBadges(id, items, type) {
        const container = document.getElementById(id);

        if (!container) return;

        const colorClass = type === 'production'
            ? 'text-emerald-700 ring-emerald-200 dark:text-emerald-200 dark:ring-emerald-800 dark:bg-emerald-950'
            : 'text-rose-700 ring-rose-200 dark:text-rose-200 dark:ring-rose-800 dark:bg-rose-950';

        container.innerHTML = items.map(item => `
        <li class="rounded-full bg-white px-3 py-1 text-xs font-semibold ring-1 ${colorClass}">
            ${escapeHtml(item)}
        </li>
    `).join('');
    }

    function renderList(id, items, type) {
        const container = document.getElementById(id);

        if (!container) {
            return;
        }

        if (!items.length) {
            container.innerHTML = `
            <li class="text-slate-500 dark:text-slate-400">
                No data.
            </li>
        `;
            return;
        }

        container.innerHTML = items.map((item, index) => {
            if (type === 'ol') {
                return `
                <li class="flex gap-2">
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200">
                        ${index + 1}
                    </span>
                    <span>${escapeHtml(item)}</span>
                </li>
            `;
            }

            return `
            <li class="flex gap-2">
                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-500"></span>
                <span>${escapeHtml(item)}</span>
            </li>
        `;
        }).join('');
    }

    function renderActionPresets(scenario) {
        renderPresetButtons(
            'naive-real-request-buttons',
            'naive',
            'single',
            scenario.action_presets?.real_requests || [],
            'btn-primary',
            'requests'
        );

        renderPresetButtons(
            'naive-simulation-buttons',
            'naive',
            'batch_race',
            scenario.action_presets?.race_simulation || [],
            'btn-primary',
            'simulate'
        );

        renderPresetButtons(
            'production-real-request-buttons',
            'production',
            'single',
            scenario.action_presets?.real_requests || [],
            'btn-success',
            'requests'
        );

        renderPresetButtons(
            'production-simulation-buttons',
            'production',
            'batch_race',
            scenario.action_presets?.race_simulation || [],
            'btn-success',
            'simulate'
        );
    }

    function renderLearningCenter(tab) {
        currentLearningTab = tab;

        updateLearningTabs(tab);

        if (tab === 'overview') {
            renderLearningOverview(currentLearningCenter.overview || {});
            return;
        }

        if (tab === 'code') {
            renderLearningCode(currentLearningCenter.code_examples || []);
            return;
        }

        if (tab === 'sequence') {
            renderLearningSequence(currentLearningCenter.sequence_diagrams || []);
            return;
        }

        if (tab === 'database') {
            renderLearningDatabase(currentLearningCenter.database_schemas || []);
            return;
        }

        if (tab === 'tradeoff') {
            renderLearningTradeOffs(currentLearningCenter.trade_offs || []);
            return;
        }
    }

    function updateLearningTabs(activeTab) {
        document.querySelectorAll('[data-learning-tab]').forEach(button => {
            const isActive = button.getAttribute('data-learning-tab') === activeTab;

            button.classList.toggle('learning-tab-active', isActive);
        });
    }

    function renderLearningOverview(overview) {
        const container = document.getElementById('learning-center-content');

        container.innerHTML = `
        <div class="grid gap-4 lg:grid-cols-4">
            ${renderOverviewCard('Problem', overview.problem)}
            ${renderOverviewCard('Failure', overview.failure)}
            ${renderOverviewCard('Solution', overview.solution)}
            ${renderOverviewCard('Cost', overview.cost)}
        </div>
    `;
    }

    function renderOverviewCard(title, content) {
        return `
        <div class="learning-card">
            <h3 class="mb-2 text-sm font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                ${escapeHtml(title)}
            </h3>
            <p class="text-sm leading-6 text-slate-700 dark:text-slate-300">
                ${escapeHtml(content || '-')}
            </p>
        </div>
    `;
    }

    function renderLearningCode(items) {
        const container = document.getElementById('learning-center-content');

        if (!items.length) {
            container.innerHTML = renderEmptyLearning();
            return;
        }

        container.innerHTML = `
        <div class="grid gap-5 lg:grid-cols-2">
            ${items.map(item => `
                <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div>
                            <div class="text-sm font-bold ${item.type === 'production' ? 'text-emerald-600' : 'text-rose-600'}">
                                ${escapeHtml(item.title)}
                            </div>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                                ${escapeHtml(item.description || '')}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="rounded-lg bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200"
                            data-copy-code
                        >
                            Copy
                        </button>
                    </div>

                    <pre class="learning-code"><code>${escapeHtml(item.code || '')}</code></pre>
                </div>
            `).join('')}
        </div>
    `;
    }

    function renderLearningSequence(items) {
        const container = document.getElementById('learning-center-content');

        if (!items.length) {
            container.innerHTML = renderEmptyLearning();
            return;
        }

        container.innerHTML = `
        <div class="grid gap-5 lg:grid-cols-2">
            ${items.map(item => `
                <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="mb-3 text-sm font-bold ${item.type === 'production' ? 'text-emerald-600' : 'text-rose-600'}">
                        ${escapeHtml(item.title)}
                    </h3>

                    <pre class="learning-code whitespace-pre-wrap"><code>${escapeHtml(item.content || '')}</code></pre>
                </div>
            `).join('')}
        </div>
    `;
    }

    function renderLearningDatabase(items) {
        const container = document.getElementById('learning-center-content');

        if (!items.length) {
            container.innerHTML = renderEmptyLearning();
            return;
        }

        container.innerHTML = `
        <div class="grid gap-5 lg:grid-cols-2">
            ${items.map(item => `
                <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="mb-3 text-sm font-bold text-indigo-600 dark:text-indigo-400">
                        ${escapeHtml(item.title)}
                    </h3>

                    <pre class="learning-code whitespace-pre-wrap"><code>${escapeHtml(item.code || '')}</code></pre>
                </div>
            `).join('')}
        </div>
    `;
    }

    function renderLearningTradeOffs(items) {
        const container = document.getElementById('learning-center-content');

        if (!items.length) {
            container.innerHTML = renderEmptyLearning();
            return;
        }

        container.innerHTML = `
        <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
            <table class="w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                <thead class="bg-slate-50 dark:bg-slate-950">
                    <tr>
                        <th class="px-4 py-3 text-left font-bold text-slate-700 dark:text-slate-300">Technique</th>
                        <th class="px-4 py-3 text-left font-bold text-slate-700 dark:text-slate-300">Pros</th>
                        <th class="px-4 py-3 text-left font-bold text-slate-700 dark:text-slate-300">Cons</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                    ${items.map(item => `
                        <tr>
                            <td class="px-4 py-4 align-top font-semibold text-slate-900 dark:text-white">
                                ${escapeHtml(item.technique || '-')}
                            </td>
                            <td class="px-4 py-4 align-top text-slate-600 dark:text-slate-400">
                                ${renderBulletList(item.pros || [])}
                            </td>
                            <td class="px-4 py-4 align-top text-slate-600 dark:text-slate-400">
                                ${renderBulletList(item.cons || [])}
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
    }

    function renderBulletList(items) {
        if (!items.length) {
            return '-';
        }

        return `
        <ul class="space-y-1">
            ${items.map(item => `
                <li class="flex gap-2">
                    <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-slate-400"></span>
                    <span>${escapeHtml(item)}</span>
                </li>
            `).join('')}
        </ul>
    `;
    }

    function renderEmptyLearning() {
        return `
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 text-sm text-slate-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400">
            No learning content.
        </div>
    `;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function renderPresetButtons(id, mode, runMode, counts, buttonClass, labelType) {
        const container = document.getElementById(id);

        if (!container) return;

        container.innerHTML = counts.map(count => {
            const label = labelType === 'simulate'
                ? `Simulate ${count}`
                : `${count} request`;

            return `
            <button data-run mode="${mode}" count="${count}" run-mode="${runMode}" class="${buttonClass}">
                ${label}
            </button>
        `;
        }).join('');
    }

    document.addEventListener('click', event => {
        const runButton = event.target.closest('[data-run]');
        const resetButton = event.target.closest('[data-reset]');
        const resetAllButton = event.target.closest('[data-reset-all]');
        const customRunButton = event.target.closest('[data-run-custom]');

        const learningTab = event.target.closest('[data-learning-tab]');

        if (learningTab) {
            renderLearningCenter(learningTab.getAttribute('data-learning-tab'));
            return;
        }

        const copyButton = event.target.closest('[data-copy-code]');

        if (copyButton) {
            const card = copyButton.closest('div.rounded-2xl');
            const code = card?.querySelector('code')?.innerText || '';

            navigator.clipboard.writeText(code);

            copyButton.innerText = 'Copied';

            setTimeout(() => {
                copyButton.innerText = 'Copy';
            }, 1200);

            return;
        }

        if (runButton) {
            run(
                runButton.getAttribute('mode'),
                Number(runButton.getAttribute('count')),
                runButton.getAttribute('run-mode') || 'single',
            );
        }

        if (customRunButton) {
            const inputTarget = customRunButton.getAttribute('input-target');
            const input = document.querySelector(`[data-custom-count="${inputTarget}"]`);

            const count = Number(input?.value || 1);
            const min = Number(input?.getAttribute('min') || 1);
            const max = Number(input?.getAttribute('max') || 100);

            const safeCount = Math.max(min, Math.min(count, max));

            input.value = safeCount;

            run(
                customRunButton.getAttribute('mode'),
                safeCount,
                customRunButton.getAttribute('run-mode') || 'single',
            );
        }

        if (resetButton) {
            reset(resetButton.getAttribute('mode'));
        }

        if (resetAllButton) {
            resetAll();
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('learning-center-content');
        if (container?.dataset.learningCenter) {
            try {
                currentLearningCenter = JSON.parse(container.dataset.learningCenter || '{}');
                renderLearningCenter('overview');
            } catch (error) {
                console.error('Invalid learning center JSON.', error);
            }
        }
    });

    document
        .getElementById('scenario-select')
        .addEventListener('change', refreshState);

    refreshState();
})();
