function buildPayload(schema) {
    const payload = {
        format: document.getElementById('format')?.value || 'sql',
        seed: null,
        tables: {},
        insert: false,
        connection: null,
    };

    const seedInput = document.getElementById('seed');
    if (seedInput && seedInput.value !== '') {
        const parsedSeed = parseInt(seedInput.value, 10);
        payload.seed = Number.isNaN(parsedSeed) ? null : parsedSeed;
    }

    const insertEnabled = document.getElementById('insert-enabled');
    if (insertEnabled?.checked) {
        payload.insert = true;
        payload.connection = {
            driver: document.getElementById('insert-db-driver')?.value || 'mysql',
            host: document.getElementById('insert-db-host')?.value || '',
            port: document.getElementById('insert-db-port')?.value || null,
            database: document.getElementById('insert-db-database')?.value || '',
            username: document.getElementById('insert-db-username')?.value || '',
            password: document.getElementById('insert-db-password')?.value || '',
        };
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
            const slugSourceSelect = document.querySelector(
                `[data-slug-source][data-table="${table.name}"][data-column="${column.name}"]`
            );
            payload.tables[table.name].columns[column.name] = {
                provider: providerSelect ? providerSelect.value : '',
                slugSourceColumn: slugSourceSelect && slugSourceSelect.value !== '' ? slugSourceSelect.value : null,
            };
        });
    });

    return payload;
}

function isTextLikeDataType(dataType) {
    if (!dataType) {
        return false;
    }
    const normalized = dataType.toLowerCase();
    return (
        normalized.includes('char')
        || normalized.includes('text')
        || normalized.includes('uuid')
        || normalized.includes('citext')
    );
}

function getTableSchema(schema, tableName) {
    return schema.tables.find((table) => table.name === tableName);
}

function populateSlugSourceSelect(schema, tableName, columnName) {
    const slugSelect = document.querySelector(
        `[data-slug-source][data-table="${tableName}"][data-column="${columnName}"]`
    );
    if (!slugSelect) {
        return;
    }
    const tableSchema = getTableSchema(schema, tableName);
    if (!tableSchema) {
        return;
    }

    const options = tableSchema.columns
        .filter((column) => column.name !== columnName && isTextLikeDataType(column.dataType))
        .map((column) => ({
            value: column.name,
            label: column.name,
        }));

    slugSelect.innerHTML = '';
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = options.length > 0 ? 'Select source column' : 'No text columns available';
    slugSelect.appendChild(placeholder);

    options.forEach((option) => {
        const optionEl = document.createElement('option');
        optionEl.value = option.value;
        optionEl.textContent = option.label;
        slugSelect.appendChild(optionEl);
    });

    slugSelect.disabled = options.length === 0;
}

function updateSlugSourceVisibility(tableName, columnName) {
    const providerSelect = document.querySelector(
        `[data-provider][data-table="${tableName}"][data-column="${columnName}"]`
    );
    const slugSelect = document.querySelector(
        `[data-slug-source][data-table="${tableName}"][data-column="${columnName}"]`
    );
    const container = slugSelect?.closest('[data-slug-source-container]');
    if (!providerSelect || !slugSelect || !container) {
        return;
    }

    if (providerSelect.value === 'text.slug') {
        container.classList.remove('hidden');
        slugSelect.disabled = slugSelect.options.length <= 1;
    } else {
        container.classList.add('hidden');
        slugSelect.value = '';
        slugSelect.disabled = true;
    }
}

function validateSlugSelections(schema) {
    const errors = [];
    schema.tables.forEach((table) => {
        table.columns.forEach((column) => {
            const providerSelect = document.querySelector(
                `[data-provider][data-table="${table.name}"][data-column="${column.name}"]`
            );
            if (!providerSelect || providerSelect.value !== 'text.slug') {
                return;
            }
            const slugSelect = document.querySelector(
                `[data-slug-source][data-table="${table.name}"][data-column="${column.name}"]`
            );
            if (!slugSelect || slugSelect.value === '') {
                errors.push(`Select a slug source column for ${table.name}.${column.name}.`);
            }
        });
    });

    return errors;
}

function removeTableFromSchema(schema, tableName) {
    if (!schema || !Array.isArray(schema.tables)) {
        return;
    }
    schema.tables = schema.tables.filter((table) => table.name !== tableName);
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

    form.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-delete-table]');
        if (!removeButton) {
            return;
        }
        const tableName = removeButton.getAttribute('data-delete-table');
        if (!tableName) {
            return;
        }
        const card = removeButton.closest('[data-table-card]');
        if (card) {
            card.remove();
        }
        removeTableFromSchema(window.generatorSchema, tableName);
    });

    window.generatorSchema.tables.forEach((table) => {
        table.columns.forEach((column) => {
            populateSlugSourceSelect(window.generatorSchema, table.name, column.name);
            updateSlugSourceVisibility(table.name, column.name);
            const providerSelect = document.querySelector(
                `[data-provider][data-table="${table.name}"][data-column="${column.name}"]`
            );
            if (providerSelect) {
                providerSelect.addEventListener('change', () => {
                    updateSlugSourceVisibility(table.name, column.name);
                });
            }
        });
    });

    const resetGenerateButton = () => {
        generateButton.disabled = false;
        generateButton.textContent = 'Generate Data';
    };

    const handleSubmit = async () => {
        const slugErrors = validateSlugSelections(window.generatorSchema);
        if (slugErrors.length > 0) {
            setJobAlert({
                status: 'failed',
                message: slugErrors.join(' '),
                showRetry: false,
            });
            setAlertVariant('failed');
            return;
        }
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
                resetGenerateButton();
                return;
            } else {
                if (result.status === 'completed') {
                    resetGenerateButton();
                    return;
                }
                await pollJobStatus(result.job_id, () => {
                    resetGenerateButton();
                });
            }
        } catch (error) {
            setJobAlert({
                status: 'failed',
                message: error.message || 'Error starting generation.',
                showRetry: true,
            });
            setAlertVariant('failed');
            resetGenerateButton();
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
