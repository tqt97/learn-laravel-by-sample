window.LabLearningCenter = (() => {
    const { escapeHtml, copyText } = window.LabUtils;

    let learningCenterState = {};
    let activeLearningTab = 'overview';

    function setLearningCenterState(state) {
        learningCenterState = state || {};
    }

    function getActiveLearningTab() {
        return activeLearningTab;
    }

    function renderLearningCenterTab(tab = activeLearningTab) {
        activeLearningTab = tab;

        updateLearningTabState(tab);

        if (tab === 'overview') {
            renderLearningOverview(learningCenterState.overview || {});
            return;
        }

        if (tab === 'code') {
            renderLearningCode(learningCenterState.code_examples || []);
            return;
        }

        if (tab === 'sequence') {
            renderLearningSequence(learningCenterState.sequence_diagrams || []);
            return;
        }

        if (tab === 'database') {
            renderLearningDatabase(learningCenterState.database_schemas || []);
            return;
        }

        if (tab === 'tradeoff') {
            renderLearningTradeOffs(learningCenterState.trade_offs || []);
        }
    }

    function updateLearningTabState(activeTab) {
        document.querySelectorAll('[data-learning-tab]').forEach(button => {
            const isActive = button.getAttribute('data-learning-tab') === activeTab;

            button.classList.toggle('learning-tab-active', isActive);
        });
    }

    function renderLearningOverview(overview) {
        const container = getLearningContainer();

        if (!container) {
            return;
        }

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
        const container = getLearningContainer();

        if (!container) {
            return;
        }

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
        const container = getLearningContainer();

        if (!container) {
            return;
        }

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
        const container = getLearningContainer();

        if (!container) {
            return;
        }

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
        const container = getLearningContainer();

        if (!container) {
            return;
        }

        if (!items.length) {
            container.innerHTML = renderEmptyLearning();
            return;
        }

        container.innerHTML = `
            <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800">
                <table class="w-full min-w-[720px] divide-y divide-slate-200 text-sm dark:divide-slate-800">
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

    function getLearningContainer() {
        return document.getElementById('learning-center-content');
    }

    async function handleCopyCode(copyButton) {
        const card = copyButton.closest('div.rounded-2xl');
        const code = card?.querySelector('code')?.innerText || '';

        await copyText(code);

        copyButton.innerText = 'Copied';

        setTimeout(() => {
            copyButton.innerText = 'Copy';
        }, 1200);
    }

    return {
        setLearningCenterState,
        getActiveLearningTab,
        renderLearningCenterTab,
        handleCopyCode,
    };
})();
