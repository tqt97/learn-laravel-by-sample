window.LabUtils = (() => {
    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function clampNumber(value, min, max) {
        const number = Number(value || min);

        if (Number.isNaN(number)) {
            return min;
        }

        return Math.max(min, Math.min(number, max));
    }

    async function copyText(text) {
        await navigator.clipboard.writeText(text);
    }

    return {
        escapeHtml,
        clampNumber,
        copyText,
    };
})();
