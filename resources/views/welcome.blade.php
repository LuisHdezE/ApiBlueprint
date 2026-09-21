<!doctype html>
<html lang="en">
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
        .workspace { display:grid; grid-template-columns:300px 1fr; gap:18px; margin-top:18px; }
        .panel { background:white; border:1px solid #dfe6ef; border-radius:22px; padding:20px; box-shadow:0 14px 40px rgba(29,53,87,.05); }
        label.meta { display:block; font-size:11px; text-transform:uppercase; font-weight:900; letter-spacing:.1em; color:#7b8798; margin:18px 0 8px; }
        input[type=text],select { width:100%; border:1px solid #d9e2ed; background:#fbfcfe; border-radius:12px; padding:11px 12px; color:#243249; }
        .summary { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:18px; }
        .summary div { background:#f5f8fc; border-radius:14px; padding:13px; }
        .summary b { display:block; font-size:21px; }
        .summary span { font-size:11px; color:#718096; }
        .export { width:100%; margin-top:18px; border:0; border-radius:14px; padding:13px 16px; background:#2563eb; color:#fff; font-weight:850; cursor:pointer; }
        .export:hover { background:#1d4ed8; }
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
            <div class="eyebrow">Build only what the contract exposes</div>
            <h1>Shape the API before the API shapes the project.</h1>
            <p>Choose a governed template, edit its endpoint surface and export an explicit blueprint manifest. Unselected endpoints do not belong to the exported contract.</p>
        </div>
        <aside class="hero-note">
            <div class="stat" id="hero-count">0 endpoints</div>
            <p>The first executable slice already treats endpoint exposure as product configuration, not Swagger decoration.</p>
        </aside>
    </header>

    <div class="section-title"><h2>1. Choose a starting template</h2><p>Every template stays editable.</p></div>
    <section id="templates" class="templates"><div class="loading">Loading blueprint catalog…</div></section>

    <div class="section-title"><h2>2. Edit the exported surface</h2><p>Enable endpoints and choose their exposure profile.</p></div>
    <section class="workspace">
        <aside class="panel">
            <label class="meta" for="project-name">Project name</label>
            <input id="project-name" type="text" value="my-api" autocomplete="off">

            <label class="meta">Selected template</label>
            <div id="selected-template">—</div>

            <div class="summary">
                <div><b id="enabled-count">0</b><span>enabled</span></div>
                <div><b id="capability-count">0</b><span>capabilities</span></div>
            </div>

            <button id="export" class="export" type="button">Export solution manifest</button>
            <p class="footnote">Baseline export produces the canonical <code>.apiblueprint.json</code> contract. Code-generation/ZIP export will consume this same contract in the next delivery slice.</p>
        </aside>

        <div class="panel">
            <div class="endpoint-head"><span></span><span>Method</span><span>Endpoint</span><span>Exposure</span></div>
            <div id="endpoints"><div class="loading">Choose a template to begin.</div></div>
        </div>
    </section>
</div>

<script>
(() => {
    const state = { catalog: null, template: null, selected: new Map() };
    const templatesEl = document.getElementById('templates');
    const endpointsEl = document.getElementById('endpoints');
    const selectedTemplateEl = document.getElementById('selected-template');
    const enabledCountEl = document.getElementById('enabled-count');
    const capabilityCountEl = document.getElementById('capability-count');
    const heroCountEl = document.getElementById('hero-count');

    const escapeHtml = value => String(value).replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));

    function applyTemplate(template) {
        state.template = template;
        const enabled = new Set(template.endpoints);
        state.selected = new Map(state.catalog.endpoints.map(endpoint => [endpoint.id, {
            enabled: enabled.has(endpoint.id),
            exposure: endpoint.default_exposure,
        }]));
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
                <input class="toggle" type="checkbox" data-toggle="${escapeHtml(endpoint.id)}" ${selection.enabled ? 'checked' : ''} aria-label="Enable ${escapeHtml(endpoint.id)}">
                <span class="method">${escapeHtml(endpoint.method)}</span>
                <span class="path"><code>${escapeHtml(endpoint.path)}</code><small>${escapeHtml(endpoint.capability)}</small></span>
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

    function renderSummary() {
        const enabled = state.catalog.endpoints.filter(endpoint => state.selected.get(endpoint.id)?.enabled);
        const capabilities = new Set(enabled.map(endpoint => endpoint.capability));
        selectedTemplateEl.textContent = state.template?.name ?? 'Custom';
        enabledCountEl.textContent = enabled.length;
        capabilityCountEl.textContent = capabilities.size;
        heroCountEl.textContent = `${enabled.length} endpoint${enabled.length === 1 ? '' : 's'}`;
    }

    function render() {
        renderTemplates();
        renderEndpoints();
        renderSummary();
    }

    function exportManifest() {
        const projectName = document.getElementById('project-name').value.trim() || 'my-api';
        const endpoints = state.catalog.endpoints
            .filter(endpoint => state.selected.get(endpoint.id)?.enabled)
            .map(endpoint => ({
                id: endpoint.id,
                capability: endpoint.capability,
                method: endpoint.method,
                path: endpoint.path,
                exposure: state.selected.get(endpoint.id).exposure,
            }));

        const manifest = {
            schema_version: state.catalog.schema_version,
            generator: 'ApiBlueprint',
            project: { name: projectName, api_version: state.catalog.api_version },
            template: state.template?.id ?? 'custom',
            endpoints,
        };

        const blob = new Blob([JSON.stringify(manifest, null, 2) + '\n'], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.download = `${projectName.replace(/[^a-z0-9-_]+/gi, '-').toLowerCase()}.apiblueprint.json`;
        anchor.click();
        URL.revokeObjectURL(url);
    }

    document.getElementById('export').addEventListener('click', exportManifest);

    fetch('/api/v1/blueprint/catalog', { headers: { Accept: 'application/json' } })
        .then(response => {
            if (!response.ok) throw new Error(`Catalog request failed: ${response.status}`);
            return response.json();
        })
        .then(catalog => {
            state.catalog = catalog;
            applyTemplate(catalog.templates.find(template => template.id === 'saas') ?? catalog.templates[0]);
        })
        .catch(error => {
            templatesEl.innerHTML = `<div class="loading">Unable to load catalog: ${escapeHtml(error.message)}</div>`;
            endpointsEl.innerHTML = '<div class="loading">The API catalog is unavailable.</div>';
        });
})();
</script>
</body>
</html>
