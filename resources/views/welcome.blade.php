<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ApiBlueprint</title>
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background:#f5f7fb; color:#17243a; }
        * { box-sizing:border-box; }
        body { margin:0; background:linear-gradient(180deg,#fbfcff 0,#f5f7fb 34%,#eef2f7 100%); min-height:100vh; }
        button,select,input { font:inherit; }
        .shell { max-width:1240px; margin:0 auto; padding:34px 24px 72px; }
        nav { display:flex; align-items:center; justify-content:space-between; gap:18px; padding-bottom:34px; }
        .brand { display:flex; align-items:center; gap:12px; font-weight:850; letter-spacing:-.03em; }
        .mark { width:38px; height:38px; border-radius:12px; display:grid; place-items:center; background:#17243a; color:white; font-weight:900; }
        .pill { border:1px solid #dbe4f0; background:#fff; padding:9px 13px; border-radius:999px; font-size:12px; font-weight:800; color:#506177; }
        .hero { display:grid; grid-template-columns:1.2fr .8fr; gap:32px; align-items:end; padding:38px 0 48px; }
        .eyebrow { color:#2563eb; font-size:12px; font-weight:900; letter-spacing:.13em; text-transform:uppercase; }
        h1 { max-width:820px; font-size:clamp(46px,7vw,82px); line-height:.94; margin:18px 0; letter-spacing:-.065em; }
        .hero p { max-width:690px; margin:0; color:#637189; line-height:1.75; font-size:17px; }
        .hero-note { background:#17243a; color:white; border-radius:26px; padding:24px; box-shadow:0 24px 60px rgba(23,36,58,.18); }
        .hero-note p { color:#bac6d8; font-size:14px; }
        .stat { font-size:34px; font-weight:900; letter-spacing:-.05em; }
        .section-title { display:flex; justify-content:space-between; align-items:end; gap:18px; margin:32px 0 16px; }
        .section-title h2 { margin:0; font-size:25px; letter-spacing:-.035em; }
        .section-title p { margin:0; font-size:13px; color:#748198; }
        .templates { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; }
        .template { text-align:left; min-height:165px; padding:20px; background:white; border:1px solid #dfe6ef; border-radius:20px; cursor:pointer; transition:.18s ease; }
        .template:hover { transform:translateY(-2px); border-color:#9bb7e5; box-shadow:0 15px 35px rgba(29,53,87,.08); }
        .template.active { border:2px solid #2563eb; box-shadow:0 0 0 4px rgba(37,99,235,.08); }
        .template strong { display:block; margin-bottom:8px; color:#17243a; }
        .template span { display:block; color:#708097; font-size:13px; line-height:1.55; }
        .workspace { display:grid; grid-template-columns:330px 1fr; gap:18px; margin-top:18px; }
        .panel { background:white; border:1px solid #dfe6ef; border-radius:22px; padding:20px; box-shadow:0 14px 40px rgba(29,53,87,.05); }
        label.meta { display:block; font-size:11px; text-transform:uppercase; font-weight:900; letter-spacing:.1em; color:#7b8798; margin:18px 0 8px; }
        input[type=text],input[type=number],select { width:100%; border:1px solid #d9e2ed; background:#fbfcfe; border-radius:12px; padding:11px 12px; color:#243249; }
        .summary { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:18px; }
        .summary div { background:#f5f8fc; border-radius:14px; padding:13px; }
        .summary b { display:block; font-size:21px; }
        .summary span { font-size:11px; color:#718096; }
        .governance { margin-top:18px; padding-top:2px; border-top:1px solid #edf1f6; }
        .governance-grid { display:grid; gap:9px; margin-top:10px; }
        .switch-row { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:10px 11px; background:#f7f9fc; border-radius:12px; font-size:12px; color:#45556d; }
        .switch-row input { width:18px; height:18px; accent-color:#2563eb; }
        .inline-fields { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
        .inline-fields label { font-size:11px; color:#7b8798; }
        .inline-fields input { margin-top:5px; }
        .export { width:100%; margin-top:18px; border:0; border-radius:14px; padding:13px 16px; background:#2563eb; color:#fff; font-weight:850; cursor:pointer; }
        .export:hover { background:#1d4ed8; }
        .export:disabled { opacity:.55; cursor:wait; }
        .notice { margin-top:12px; border:1px solid #c9dcff; background:#eff6ff; color:#24466f; border-radius:12px; padding:11px 12px; font-size:12px; line-height:1.5; }
        .notice.error { border-color:#fecaca; background:#fef2f2; color:#991b1b; }
        .endpoint-head,.endpoint { display:grid; grid-template-columns:38px 92px 1fr 175px; gap:12px; align-items:center; }
        .endpoint-head { padding:0 10px 10px; color:#8793a5; font-size:11px; font-weight:850; text-transform:uppercase; letter-spacing:.08em; }
        .endpoint { padding:12px 10px; border-top:1px solid #edf1f6; }
        .endpoint:first-of-type { border-top:0; }
        .endpoint.off { opacity:.46; }
        .method { font-size:11px; font-weight:950; letter-spacing:.06em; }
        .path { min-width:0; }
        .path code { font-size:12px; color:#34455e; word-break:break-all; }
        .path small { display:block; color:#8b97a8; margin-top:4px; }
        .toggle { width:18px; height:18px; accent-color:#2563eb; }
        .loading { padding:48px; text-align:center; color:#718096; }
        .footnote { margin-top:14px; color:#7a8798; font-size:12px; line-height:1.6; }
        @media (max-width:960px) { .hero{grid-template-columns:1fr}.templates{grid-template-columns:1fr 1fr}.workspace{grid-template-columns:1fr}.hero-note{max-width:520px}.endpoint-head{display:none}.endpoint{grid-template-columns:32px 80px 1fr}.endpoint select{grid-column:2/4} }
        @media (max-width:620px) { .templates{grid-template-columns:1fr}.shell{padding-inline:16px}.hero{padding-top:20px}.endpoint{grid-template-columns:28px 68px 1fr;padding-inline:0}.path code{font-size:11px} }
    </style>
</head>
<body>
<div class="shell">
    <nav>
        <div class="brand"><span class="mark">A</span> ApiBlueprint</div>
        <span class="pill">Laravel 13 · Clean Architecture</span>
    </nav>

    <header class="hero">
        <div>
            <div class="eyebrow">Contrato, seguridad e infraestructura desde el mismo blueprint</div>
            <h1>Diseña la API antes de que la API diseñe tu proyecto.</h1>
            <p>Elige una plantilla, edita endpoints y gobierna las capacidades transversales. La solución exportada contiene solamente la superficie y la infraestructura que el manifest resuelto exige.</p>
        </div>
        <aside class="hero-note">
            <div class="stat" id="hero-count">0 endpoints</div>
            <p>Autenticación, RBAC, correlación, rate limiting, idempotencia y auditoría se convierten en código exportado, no en simples preferencias visuales.</p>
        </aside>
    </header>

    <div class="section-title"><h2>1. Elige una plantilla inicial</h2><p>Todas las plantillas permanecen editables.</p></div>
    <section id="templates" class="templates"><div class="loading">Cargando catálogo del blueprint…</div></section>

    <div class="section-title"><h2>2. Edita la superficie y su gobierno</h2><p>Endpoints, exposición y capacidades transversales forman un único contrato.</p></div>
    <section class="workspace">
        <aside class="panel">
            <label class="meta" for="project-name">Nombre del proyecto</label>
            <input id="project-name" type="text" value="mi-api" autocomplete="off">

            <label class="meta">Plantilla seleccionada</label>
            <div id="selected-template">—</div>

            <div class="summary">
                <div><b id="enabled-count">0</b><span>habilitados</span></div>
                <div><b id="capability-count">0</b><span>capacidades</span></div>
            </div>

            <div class="governance">
                <label class="meta" for="authentication">Autenticación</label>
                <select id="authentication"></select>

                <label class="meta" for="pagination-strategy">Paginación</label>
                <select id="pagination-strategy"></select>
                <div class="inline-fields">
                    <label>Por defecto<input id="pagination-default" type="number" min="1" max="500"></label>
                    <label>Máximo<input id="pagination-max" type="number" min="1" max="500"></label>
                </div>

                <label class="meta">Capacidades transversales</label>
                <div class="governance-grid">
                    <label class="switch-row"><span>RBAC</span><input id="rbac" type="checkbox"></label>
                    <label class="switch-row"><span>Correlation ID</span><input id="correlation-id" type="checkbox"></label>
                    <label class="switch-row"><span>Rate limiting</span><input id="rate-limiting" type="checkbox"></label>
                    <div class="inline-fields"><label>Solicitudes/minuto<input id="rate-limit" type="number" min="1" max="1000"></label></div>
                    <label class="switch-row"><span>Filtrado estándar</span><input id="filtering" type="checkbox"></label>
                    <label class="switch-row"><span>Ordenamiento estándar</span><input id="sorting" type="checkbox"></label>
                    <label class="switch-row"><span>Idempotencia</span><input id="idempotency" type="checkbox"></label>
                    <label class="switch-row"><span>Auditoría</span><input id="audit" type="checkbox"></label>
                </div>
            </div>

            <button id="export" class="export" type="button">Exportar solución ZIP</button>
            <div id="notice" class="notice" hidden></div>
            <p class="footnote">El backend canonicaliza el manifest y valida dependencias entre exposición, autenticación y RBAC antes de generar el paquete.</p>
        </aside>

        <div class="panel">
            <div class="endpoint-head"><span></span><span>Método</span><span>Endpoint</span><span>Exposición</span></div>
            <div id="endpoints"><div class="loading">Elige una plantilla para comenzar.</div></div>
        </div>
    </section>
</div>

<script>
(() => {
    const state = { catalog: null, template: null, selected: new Map(), governance: null };
    const templatesEl = document.getElementById('templates');
    const endpointsEl = document.getElementById('endpoints');
    const selectedTemplateEl = document.getElementById('selected-template');
    const enabledCountEl = document.getElementById('enabled-count');
    const capabilityCountEl = document.getElementById('capability-count');
    const heroCountEl = document.getElementById('hero-count');
    const exportButton = document.getElementById('export');
    const noticeEl = document.getElementById('notice');
    const authenticationEl = document.getElementById('authentication');
    const paginationStrategyEl = document.getElementById('pagination-strategy');

    const escapeHtml = value => String(value).replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
    const clone = value => JSON.parse(JSON.stringify(value));

    function showNotice(message, isError = false) {
        noticeEl.hidden = false;
        noticeEl.textContent = message;
        noticeEl.classList.toggle('error', isError);
    }

    function applyTemplate(template) {
        state.template = template;
        const enabled = new Set(template.endpoints);
        state.selected = new Map(state.catalog.endpoints.map(endpoint => [endpoint.id, {
            enabled: enabled.has(endpoint.id),
            exposure: endpoint.default_exposure,
        }]));
        state.governance = clone(state.catalog.governance.defaults);
        noticeEl.hidden = true;
        render();
    }

    function renderTemplates() {
        templatesEl.innerHTML = state.catalog.templates.map(template => `
            <button class="template ${state.template?.id === template.id ? 'active' : ''}" data-template="${escapeHtml(template.id)}" type="button">
                <strong>${escapeHtml(template.name)}</strong>
                <span>${escapeHtml(template.description)}</span>
            </button>`).join('');

        templatesEl.querySelectorAll('[data-template]').forEach(button => {
            button.addEventListener('click', () => applyTemplate(state.catalog.templates.find(item => item.id === button.dataset.template)));
        });
    }

    function renderEndpoints() {
        endpointsEl.innerHTML = state.catalog.endpoints.map(endpoint => {
            const selection = state.selected.get(endpoint.id);
            return `<div class="endpoint ${selection.enabled ? '' : 'off'}" data-row="${escapeHtml(endpoint.id)}">
                <input class="toggle" type="checkbox" data-toggle="${escapeHtml(endpoint.id)}" ${selection.enabled ? 'checked' : ''} aria-label="Habilitar ${escapeHtml(endpoint.summary)}">
                <span class="method">${escapeHtml(endpoint.method)}</span>
                <span class="path"><code>${escapeHtml(endpoint.path)}</code><small>${escapeHtml(endpoint.summary)} · ${escapeHtml(endpoint.capability_label)}</small></span>
                <select data-exposure="${escapeHtml(endpoint.id)}" ${selection.enabled ? '' : 'disabled'}>
                    ${state.catalog.exposures.map(exposure => `<option value="${escapeHtml(exposure.id)}" ${selection.exposure === exposure.id ? 'selected' : ''}>${escapeHtml(exposure.label)}</option>`).join('')}
                </select>
            </div>`;
        }).join('');

        endpointsEl.querySelectorAll('[data-toggle]').forEach(input => {
            input.addEventListener('change', () => {
                state.selected.get(input.dataset.toggle).enabled = input.checked;
                renderEndpoints();
                renderSummary();
            });
        });

        endpointsEl.querySelectorAll('[data-exposure]').forEach(select => {
            select.addEventListener('change', () => state.selected.get(select.dataset.exposure).exposure = select.value);
        });
    }

    function renderGovernance() {
        authenticationEl.innerHTML = state.catalog.governance.authentication_strategies
            .map(strategy => `<option value="${escapeHtml(strategy.id)}">${escapeHtml(strategy.label)}</option>`).join('');
        paginationStrategyEl.innerHTML = state.catalog.governance.pagination_strategies
            .map(strategy => `<option value="${escapeHtml(strategy.id)}">${escapeHtml(strategy.label)}</option>`).join('');

        authenticationEl.value = state.governance.authentication;
        paginationStrategyEl.value = state.governance.pagination.strategy;
        document.getElementById('pagination-default').value = state.governance.pagination.default_size;
        document.getElementById('pagination-max').value = state.governance.pagination.max_size;
        document.getElementById('rbac').checked = state.governance.rbac;
        document.getElementById('correlation-id').checked = state.governance.correlation_id;
        document.getElementById('rate-limiting').checked = state.governance.rate_limiting.enabled;
        document.getElementById('rate-limit').value = state.governance.rate_limiting.requests_per_minute;
        document.getElementById('filtering').checked = state.governance.filtering;
        document.getElementById('sorting').checked = state.governance.sorting;
        document.getElementById('idempotency').checked = state.governance.idempotency;
        document.getElementById('audit').checked = state.governance.audit;
    }

    function readGovernance() {
        state.governance = {
            authentication: authenticationEl.value,
            rbac: document.getElementById('rbac').checked,
            correlation_id: document.getElementById('correlation-id').checked,
            rate_limiting: {
                enabled: document.getElementById('rate-limiting').checked,
                requests_per_minute: Number(document.getElementById('rate-limit').value),
            },
            pagination: {
                strategy: paginationStrategyEl.value,
                default_size: Number(document.getElementById('pagination-default').value),
                max_size: Number(document.getElementById('pagination-max').value),
            },
            filtering: document.getElementById('filtering').checked,
            sorting: document.getElementById('sorting').checked,
            idempotency: document.getElementById('idempotency').checked,
            audit: document.getElementById('audit').checked,
        };
    }

    function renderSummary() {
        const enabled = state.catalog.endpoints.filter(endpoint => state.selected.get(endpoint.id)?.enabled);
        const capabilities = new Set(enabled.map(endpoint => endpoint.capability));
        selectedTemplateEl.textContent = state.template?.name ?? 'Personalizada';
        enabledCountEl.textContent = enabled.length;
        capabilityCountEl.textContent = capabilities.size;
        heroCountEl.textContent = `${enabled.length} endpoint${enabled.length === 1 ? '' : 's'}`;
    }

    function render() {
        renderTemplates();
        renderEndpoints();
        renderGovernance();
        renderSummary();
    }

    function buildManifest() {
        readGovernance();
        const projectName = document.getElementById('project-name').value.trim() || 'mi-api';
        const endpoints = state.catalog.endpoints
            .filter(endpoint => state.selected.get(endpoint.id)?.enabled)
            .map(endpoint => ({
                id: endpoint.id,
                exposure: state.selected.get(endpoint.id).exposure,
            }));

        return {
            schema_version: state.catalog.schema_version,
            generator: 'ApiBlueprint',
            project: { name: projectName, api_version: state.catalog.api_version },
            template: state.template?.id ?? 'custom',
            governance: state.governance,
            endpoints,
        };
    }

    async function resolveManifest(manifest) {
        const response = await fetch('/api/v1/blueprint/resolve', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify(manifest),
        });
        const payload = await response.json();

        if (!response.ok) {
            const errors = payload.errors ? Object.values(payload.errors).flat().join(' ') : payload.detail;
            throw new Error(errors || 'No se pudo validar la configuración.');
        }

        return payload;
    }

    function applyResolvedManifest(resolved) {
        const resolvedById = new Map(resolved.endpoints.map(endpoint => [endpoint.id, endpoint]));

        state.catalog.endpoints.forEach(endpoint => {
            const resolvedEndpoint = resolvedById.get(endpoint.id);
            const selection = state.selected.get(endpoint.id);
            selection.enabled = Boolean(resolvedEndpoint);
            if (resolvedEndpoint) selection.exposure = resolvedEndpoint.exposure;
        });

        state.governance = clone(resolved.governance);
        renderEndpoints();
        renderGovernance();
        renderSummary();

        const messages = [];
        const autoAdded = resolved.resolution.auto_added.map(item => item.id);
        if (autoAdded.length > 0) messages.push(`Dependencias añadidas: ${autoAdded.join(', ')}.`);
        if (resolved.resolution.governance_adjustments.length > 0) messages.push('Se ajustaron capacidades transversales requeridas por la superficie.');
        showNotice(messages.join(' ') || 'Configuración validada. La selección visible coincide con lo que se exportará.');
    }

    async function exportSolution() {
        exportButton.disabled = true;
        exportButton.textContent = 'Validando y generando…';
        noticeEl.hidden = true;

        try {
            const resolved = await resolveManifest(buildManifest());
            applyResolvedManifest(resolved);

            const response = await fetch('/api/v1/blueprint/export', {
                method: 'POST',
                headers: { 'Accept': 'application/zip', 'Content-Type': 'application/json' },
                body: JSON.stringify(resolved),
            });

            if (!response.ok) {
                const payload = await response.json();
                const errors = payload.errors ? Object.values(payload.errors).flat().join(' ') : payload.detail;
                throw new Error(errors || 'No se pudo generar la solución.');
            }

            const blob = await response.blob();
            const url = URL.createObjectURL(blob);
            const anchor = document.createElement('a');
            const projectName = resolved.project.name.replace(/[^a-z0-9-_]+/gi, '-').toLowerCase() || 'api';
            anchor.href = url;
            anchor.download = `${projectName}.zip`;
            anchor.click();
            URL.revokeObjectURL(url);
            showNotice('Solución generada. El ZIP contiene endpoints, gobierno transversal, OpenAPI en español y pruebas contractuales derivados del mismo manifest.');
        } catch (error) {
            showNotice(error.message || 'Ocurrió un error al exportar la solución.', true);
        } finally {
            exportButton.disabled = false;
            exportButton.textContent = 'Exportar solución ZIP';
        }
    }

    exportButton.addEventListener('click', exportSolution);

    fetch('/api/v1/blueprint/catalog', { headers: { Accept: 'application/json' } })
        .then(response => {
            if (!response.ok) throw new Error(`No se pudo cargar el catálogo: ${response.status}`);
            return response.json();
        })
        .then(catalog => {
            state.catalog = catalog;
            applyTemplate(catalog.templates.find(template => template.id === 'saas') ?? catalog.templates[0]);
        })
        .catch(error => {
            templatesEl.innerHTML = `<div class="loading">No se pudo cargar el catálogo: ${escapeHtml(error.message)}</div>`;
            endpointsEl.innerHTML = '<div class="loading">El catálogo de la API no está disponible.</div>';
        });
})();
</script>
</body>
</html>
