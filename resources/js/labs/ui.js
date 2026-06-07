window.LabUi = (() => {
    function log(mode, message, type = 'info') {
        const container = document.querySelector(`[data-log="${mode}"]`);

        const line = document.createElement('div');
        line.className = 'log-line';

        const icon = type === 'success'
            ? '✅'
            : type === 'error'
                ? '❌'
                : 'ℹ️';

        line.innerText = `[${new Date().toLocaleTimeString()}] ${icon} ${message}`;

        container.prepend(line);
    }

    function clearLog(mode) {
        document.querySelector(`[data-log="${mode}"]`).innerHTML = '';
    }

    function renderMetrics(mode, metrics) {
        const container = document.querySelector(`[data-metrics="${mode}"]`);

        container.innerHTML = Object.entries(metrics)
            .map(([key, value]) => `
                <div class="metric">
                    <span>${formatLabel(key)}</span>
                    <strong>${value}</strong>
                </div>
            `)
            .join('');
    }

    function renderInvariants(mode, invariants) {
        const container = document.querySelector(`[data-invariants="${mode}"]`);

        container.innerHTML = invariants
            .map(item => `
                <div class="metric">
                    <span>${item.name}</span>
                    <strong class="${item.ok ? 'ok' : 'bad'}">
                        ${item.ok ? 'OK' : 'BROKEN'}
                    </strong>
                </div>
                <div class="muted" style="font-size: 13px; margin-bottom: 8px;">
                    ${item.message}
                </div>
            `)
            .join('');
    }

    function formatLabel(key) {
        return key
            .replaceAll('_', ' ')
            .replace(/\b\w/g, char => char.toUpperCase());
    }

    return {
        log,
        clearLog,
        renderMetrics,
        renderInvariants,
    };
})();
