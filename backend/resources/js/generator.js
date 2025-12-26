function buildPayload(schema) {
    const payload = {
        format: document.getElementById('format')?.value || 'sql',
        seed: null,
        tables: {},
    };

    const seedInput = document.getElementById('seed');
    if (seedInput && seedInput.value !== '') {
        const parsedSeed = parseInt(seedInput.value, 10);
        payload.seed = Number.isNaN(parsedSeed) ? null : parsedSeed;
    }

    schema.tables.forEach((table) => {
        const rowInput = document.querySelector(`[data-row-count][data-table="${table.name}"]`);
        const rowCount = rowInput ? parseInt(rowInput.value, 10) : 0;
        payload.tables[table.name] = {
            rowCount: Number.isNaN(rowCount) ? 0 : rowCount,
            columns: {},
        };

        table.columns.forEach((column) => {
            const providerSelect = document.querySelector(
                `[data-provider][data-table="${table.name}"][data-column="${column.name}"]`
            );
            payload.tables[table.name].columns[column.name] = {
                provider: providerSelect ? providerSelect.value : '',
            };
        });
    });

    return payload;
}

function setJobAlert({ status, message, downloadUrl, showRetry }) {
    const alert = document.getElementById('job-alert');
    const title = document.getElementById('job-title');
    const messageEl = document.getElementById('job-message');
    const retryButton = document.getElementById('job-retry');

    if (!alert || !title || !messageEl || !retryButton) {
        return;
    }

    alert.classList.remove('hidden', 'border-red-200', 'bg-red-50', 'border-green-200', 'bg-green-50');
    alert.classList.add('border-slate-200', 'bg-slate-50');

    title.textContent = `Job Status: ${status}`;
    if (downloadUrl) {
        messageEl.innerHTML = `Job completed! <a href="${downloadUrl}" class="font-semibold text-slate-900 hover:underline">Download Data</a>`;
    } else {
        messageEl.textContent = message;
    }

    retryButton.classList.toggle('hidden', !showRetry);
}

function setAlertVariant(status) {
    const alert = document.getElementById('job-alert');
    if (!alert) {
        return;
    }
    alert.classList.remove('border-slate-200', 'bg-slate-50', 'border-red-200', 'bg-red-50', 'border-green-200', 'bg-green-50');

    if (status === 'failed') {
        alert.classList.add('border-red-200', 'bg-red-50');
    } else if (status === 'completed') {
        alert.classList.add('border-green-200', 'bg-green-50');
    } else {
        alert.classList.add('border-slate-200', 'bg-slate-50');
    }
}

async function pollJobStatus(jobId, onComplete) {
    const statusUrl = `${window.generatorRoutes.jobStatusBase}/${jobId}`;

    let active = true;
    const poll = async () => {
        if (!active) {
            return;
        }
        try {
            const response = await fetch(statusUrl, {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) {
                throw new Error('status request failed');
            }
            const result = await response.json();
            setAlertVariant(result.status);
            if (result.status === 'completed') {
                setJobAlert({
                    status: result.status,
                    message: 'Job completed.',
                    downloadUrl: result.download_url,
                    showRetry: false,
                });
                onComplete?.();
                active = false;
                return;
            }
            if (result.status === 'failed') {
                setJobAlert({
                    status: result.status,
                    message: result.error || 'Job failed unexpectedly.',
                    showRetry: true,
                });
                onComplete?.();
                active = false;
                return;
            }
            setJobAlert({
                status: result.status,
                message: 'Data generation is in progress...',
                showRetry: false,
            });
        } catch (error) {
            setJobAlert({
                status: 'failed',
                message: 'Failed to fetch job status.',
                showRetry: true,
            });
            setAlertVariant('failed');
            onComplete?.();
            active = false;
        }
    };

    await poll();
    const interval = setInterval(async () => {
        if (!active) {
            clearInterval(interval);
            return;
        }
        await poll();
    }, 5000);
}

document.addEventListener('DOMContentLoaded', () => {
    if (!window.generatorSchema || !window.generatorRoutes) {
        return;
    }

    const form = document.getElementById('generation-form');
    const generateButton = document.getElementById('generate-button');
    const retryButton = document.getElementById('job-retry');

    if (!form || !generateButton) {
        return;
    }

    const handleSubmit = async () => {
        setJobAlert({
            status: 'pending',
            message: 'Starting data generation...',
            showRetry: false,
        });
        setAlertVariant('pending');

        generateButton.disabled = true;
        generateButton.textContent = 'Generating...';

        try {
            const payload = buildPayload(window.generatorSchema);
            const response = await fetch(window.generatorRoutes.generate, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                const errorPayload = await response.json();
                const messages = errorPayload?.errors
                    ? Object.values(errorPayload.errors).flat().join(' | ')
                    : 'Error starting generation.';
                throw new Error(messages);
            }

            const result = await response.json();
            setJobAlert({
                status: result.status,
                message: result.status === 'completed'
                    ? 'Job completed.'
                    : 'Data generation is in progress...',
                showRetry: false,
            });
            setAlertVariant(result.status);
            if (result.status === 'completed' && result.download_url) {
                setJobAlert({
                    status: result.status,
                    message: 'Job completed.',
                    downloadUrl: result.download_url,
                    showRetry: false,
                });
                setAlertVariant(result.status);
            } else {
                await pollJobStatus(result.job_id, () => {
                    generateButton.disabled = false;
                    generateButton.textContent = 'Generate Data';
                });
            }
        } catch (error) {
            setJobAlert({
                status: 'failed',
                message: error.message || 'Error starting generation.',
                showRetry: true,
            });
            setAlertVariant('failed');
            generateButton.disabled = false;
            generateButton.textContent = 'Generate Data';
        }
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        handleSubmit();
    });

    if (retryButton) {
        retryButton.addEventListener('click', () => {
            handleSubmit();
        });
    }
});
