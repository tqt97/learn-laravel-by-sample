window.LabScenarioMeta = (() => {
    const { escapeHtml } = window.LabUtils;

    function renderScenarioMeta(scenario) {
        setText('scenario-subtitle', scenario.subtitle);
        setText('scenario-title', scenario.title);
        setText('scenario-description', scenario.description);
        setText('scenario-action-hint', scenario.action_hint);

        setText('chart-title', scenario.ui?.chart?.title || '');
        setText('chart-description', scenario.ui?.chart?.description || '');

        setText('naive-real-requests-label', scenario.ui?.actions?.real_requests_label || 'Real Requests');
        setText('production-real-requests-label', scenario.ui?.actions?.real_requests_label || 'Real Requests');

        setText('naive-simulation-label', scenario.ui?.actions?.simulation_label || 'Simulation');
        setText('production-simulation-label', scenario.ui?.actions?.simulation_label || 'Simulation');

        setText('naive-log-title', scenario.ui?.logs?.naive_title || 'Naive Log');
        setText('production-log-title', scenario.ui?.logs?.production_title || 'Production Log');

        renderScenarioList('scenario-learning-goals', scenario.learning_goals || [], 'ul');
        renderScenarioList('scenario-how-to-use', scenario.how_to_use || [], 'ol');

        renderTechniqueBadges('naive-techniques', scenario.naive_techniques || [], 'naive');
        renderTechniqueBadges('production-techniques', scenario.production_techniques || [], 'production');

        renderActionPresetButtons(scenario);
    }

    function setText(id, value) {
        const element = document.getElementById(id);

        if (!element) {
            return;
        }

        element.innerText = value || '';
    }

    function renderScenarioList(id, items, type) {
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

    function renderTechniqueBadges(id, items, type) {
        const container = document.getElementById(id);

        if (!container) {
            return;
        }

        const colorClass = type === 'production'
            ? 'text-emerald-700 ring-emerald-200 dark:text-emerald-200 dark:ring-emerald-800 dark:bg-emerald-950'
            : 'text-rose-700 ring-rose-200 dark:text-rose-200 dark:ring-rose-800 dark:bg-rose-950';

        container.innerHTML = items.map(item => `
            <li class="rounded-full bg-white px-3 py-1 text-xs font-semibold ring-1 ${colorClass}">
                ${escapeHtml(item)}
            </li>
        `).join('');
    }

    function renderActionPresetButtons(scenario) {
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

    function renderPresetButtons(id, mode, runMode, counts, buttonClass, labelType) {
        const container = document.getElementById(id);

        if (!container) {
            return;
        }

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

    return {
        renderScenarioMeta,
    };
})();
