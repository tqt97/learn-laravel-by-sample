window.LabApi = (() => {
    function csrfToken() {
        return document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute('content');
    }

    async function request(url, options = {}) {
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                ...(options.headers || {}),
            },
            ...options,
        });

        const json = await response.json().catch(() => ({
            success: false,
            message: 'Invalid JSON response.',
        }));

        if (!response.ok) {
            throw {
                status: response.status,
                ...json,
            };
        }

        return json;
    }

    function state(scenario) {
        return request(`/labs/state/${scenario}`);
    }

    function action(scenario, mode, payload = {}) {
        return request(`/labs/action/${scenario}/${mode}`, {
            method: 'POST',
            body: JSON.stringify(payload),
        });
    }

    function reset(scenario, mode) {
        return request(`/labs/reset/${scenario}/${mode}`, {
            method: 'POST',
        });
    }

    function resetAll(scenario) {
        return request(`/labs/reset/${scenario}`, {
            method: 'POST',
        });
    }

    return {
        state,
        action,
        reset,
        resetAll,
    };
})();
