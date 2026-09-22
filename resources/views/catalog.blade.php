<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administración del catálogo · ApiBlueprint</title>
    <style>
        :root{font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#17243a;background:#f4f7fb}*{box-sizing:border-box}body{margin:0;background:#f4f7fb}.shell{max-width:1320px;margin:0 auto;padding:28px 24px 64px}nav{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:32px}.brand{font-weight:900;letter-spacing:-.03em}.links{display:flex;gap:8px;flex-wrap:wrap}.links a{text-decoration:none;color:#43536a;background:white;border:1px solid #dce5ef;border-radius:10px;padding:9px 12px;font-size:13px;font-weight:750}.links a.active{background:#17243a;color:white;border-color:#17243a}.eyebrow{font-size:12px;font-weight:900;letter-spacing:.12em;text-transform:uppercase;color:#2563eb}h1{font-size:clamp(34px,5vw,58px);letter-spacing:-.055em;margin:10px 0 12px}.lead{color:#67758b;max-width:820px;line-height:1.7}.stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:28px 0}.stat{background:white;border:1px solid #dde6ef;border-radius:18px;padding:18px}.stat b{display:block;font-size:30px;letter-spacing:-.05em}.stat span{font-size:12px;color:#718096}.section{margin-top:34px}.section-head{display:flex;justify-content:space-between;align-items:end;gap:16px;margin-bottom:14px}.section-head h2{margin:0;font-size:24px;letter-spacing:-.035em}.section-head p{margin:0;color:#7d8999;font-size:12px}.apps{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.card{background:white;border:1px solid #dfe6ef;border-radius:18px;padding:18px}.card h3{margin:8px 0;font-size:17px}.card p{font-size:12px;color:#738095;line-height:1.55;min-height:58px}.meta{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:16px;font-size:12px}.pill{display:inline-flex;align-items:center;border-radius:999px;padding:6px 9px;font-size:11px;font-weight:850;background:#edf2f7;color:#536278}.pill.ready,.pill.implemented{background:#ecfdf3;color:#166534}.pill.partial,.pill.in_progress{background:#fff7ed;color:#9a3412}.pill.planned{background:#f1f5f9;color:#64748b}.toolbar{display:flex;gap:10px;flex-wrap:wrap;margin:14px 0}.toolbar select,.toolbar input{border:1px solid #d8e2ed;background:white;border-radius:10px;padding:10px 12px;color:#334155}.toolbar input{min-width:260px;flex:1}.table-wrap{background:white;border:1px solid #dfe6ef;border-radius:18px;overflow:auto}.features{width:100%;border-collapse:collapse;min-width:1040px}.features th,.features td{text-align:left;padding:13px 14px;border-bottom:1px solid #edf1f5;font-size:12px;vertical-align:middle}.features th{font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:#8793a5;background:#fbfcfe;position:sticky;top:0}.features code{font-size:11px;color:#334155}.yes{color:#166534;font-weight:900}.no{color:#94a3b8}.loading{padding:40px;text-align:center;color:#718096}.empty{padding:28px;text-align:center;color:#718096}.legend{margin-top:12px;font-size:11px;color:#7b8798;line-height:1.7}@media(max-width:980px){.apps{grid-template-columns:1fr 1fr}.stats{grid-template-columns:1fr 1fr}}@media(max-width:620px){.shell{padding-inline:16px}.apps,.stats{grid-template-columns:1fr}nav{align-items:flex-start;flex-direction:column}.section-head{align-items:flex-start;flex-direction:column}.toolbar input{min-width:100%}}
    </style>
</head>
<body>
<div class="shell">
    <nav>
        <div class="brand">ApiBlueprint · Catálogo maestro</div>
        <div class="links">
            <a href="/">Compositor</a>
            <a class="active" href="/catalogo">Catálogo</a>
            <a href="/swagger">Swagger</a>
        </div>
    </nav>

    <header>
        <div class="eyebrow">Administración de la biblioteca</div>
        <h1>Una sola feature. Muchas aplicaciones.</h1>
        <p class="lead">Esta vista refleja la fuente canónica de ApiBlueprint. Cada aplicación reutiliza features existentes; una funcionalidad no se duplica por aplicación. Las nuevas aplicaciones entran al catálogo desde el momento en que decidimos trabajarlas.</p>
    </header>

    <section id="stats" class="stats"><div class="loading">Cargando catálogo…</div></section>

    <section class="section">
        <div class="section-head"><h2>Application Catalog</h2><p>Presets vivos construidos por composición de la biblioteca maestra.</p></div>
        <div id="applications" class="apps"><div class="loading">Cargando aplicaciones…</div></div>
    </section>

    <section class="section">
        <div class="section-head"><h2>Master Feature Library</h2><p>Implementación, Swagger, tests y reutilización visibles en un solo lugar.</p></div>
        <div class="toolbar">
            <input id="search" type="search" placeholder="Buscar feature, módulo, método o ruta…">
            <select id="status"><option value="">Todos los estados</option></select>
            <select id="capability"><option value="">Todos los módulos</option></select>
        </div>
        <div class="table-wrap">
            <table class="features">
                <thead><tr><th>Feature</th><th>Módulo</th><th>HTTP</th><th>Ruta</th><th>Estado</th><th>Export</th><th>Swagger</th><th>Tests</th><th>Aplicaciones</th></tr></thead>
                <tbody id="features"><tr><td colspan="9" class="loading">Cargando features…</td></tr></tbody>
            </table>
        </div>
        <p class="legend">Regla de biblioteca: antes de crear una feature se busca una equivalente existente. Si existe se reutiliza; si cubre parcialmente la necesidad se extiende; solo una capacidad realmente distinta obtiene un nuevo identificador canónico.</p>
    </section>
</div>
<script>
(() => {
    const state = { catalog: null };
    const statsEl = document.getElementById('stats');
    const applicationsEl = document.getElementById('applications');
    const featuresEl = document.getElementById('features');
    const searchEl = document.getElementById('search');
    const statusEl = document.getElementById('status');
    const capabilityEl = document.getElementById('capability');
    const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));

    function statusLabel(id, collection) {
        return collection.find(item => item.id === id)?.label ?? id;
    }

    function renderStats() {
        const features = state.catalog.features;
        const implemented = features.filter(item => item.implementation_status === 'implemented').length;
        const reusable = features.filter(item => item.applications.length > 1).length;
        statsEl.innerHTML = `
            <div class="stat"><b>${state.catalog.applications.length}</b><span>tipos / presets de aplicación</span></div>
            <div class="stat"><b>${features.length}</b><span>features registradas</span></div>
            <div class="stat"><b>${implemented}</b><span>features implementadas</span></div>
            <div class="stat"><b>${reusable}</b><span>features reutilizadas por varias aplicaciones</span></div>`;
    }

    function renderApplications() {
        applicationsEl.innerHTML = state.catalog.applications.map(app => `
            <article class="card">
                <span class="pill ${escapeHtml(app.status)}">${escapeHtml(statusLabel(app.status, state.catalog.application_statuses))}</span>
                <h3>${escapeHtml(app.name)}</h3>
                <p>${escapeHtml(app.description)}</p>
                <div class="meta"><span>${app.coverage.implemented}/${app.coverage.total} implementadas</span><strong>${escapeHtml(app.category)}</strong></div>
            </article>`).join('');
    }

    function renderFilters() {
        statusEl.innerHTML += state.catalog.feature_statuses.map(item => `<option value="${escapeHtml(item.id)}">${escapeHtml(item.label)}</option>`).join('');
        const capabilities = [...new Map(state.catalog.features.map(item => [item.capability, item.capability_label])).entries()].sort((a,b) => a[1].localeCompare(b[1]));
        capabilityEl.innerHTML += capabilities.map(([id, label]) => `<option value="${escapeHtml(id)}">${escapeHtml(label)}</option>`).join('');
    }

    function renderFeatures() {
        const term = searchEl.value.trim().toLowerCase();
        const status = statusEl.value;
        const capability = capabilityEl.value;
        const filtered = state.catalog.features.filter(feature => {
            const haystack = `${feature.id} ${feature.capability_label} ${feature.method} ${feature.path} ${feature.summary}`.toLowerCase();
            return (!term || haystack.includes(term)) && (!status || feature.implementation_status === status) && (!capability || feature.capability === capability);
        });

        if (!filtered.length) {
            featuresEl.innerHTML = '<tr><td colspan="9" class="empty">No hay features que coincidan con el filtro.</td></tr>';
            return;
        }

        featuresEl.innerHTML = filtered.map(feature => `
            <tr>
                <td><strong>${escapeHtml(feature.id)}</strong><br><small>${escapeHtml(feature.summary)}</small></td>
                <td>${escapeHtml(feature.capability_label)}</td>
                <td><strong>${escapeHtml(feature.method)}</strong></td>
                <td><code>${escapeHtml(feature.path)}</code></td>
                <td><span class="pill ${escapeHtml(feature.implementation_status)}">${escapeHtml(statusLabel(feature.implementation_status, state.catalog.feature_statuses))}</span></td>
                <td class="${feature.exportable ? 'yes' : 'no'}">${feature.exportable ? '✓' : '—'}</td>
                <td class="${feature.openapi_ready ? 'yes' : 'no'}">${feature.openapi_ready ? '✓' : '—'}</td>
                <td class="${feature.tests_ready ? 'yes' : 'no'}">${feature.tests_ready ? '✓' : '—'}</td>
                <td>${feature.applications.length ? feature.applications.map(escapeHtml).join(', ') : '—'}</td>
            </tr>`).join('');
    }

    [searchEl, statusEl, capabilityEl].forEach(element => element.addEventListener('input', renderFeatures));

    fetch('/api/v1/blueprint/catalog', {headers:{Accept:'application/json'}})
        .then(response => { if (!response.ok) throw new Error(`HTTP ${response.status}`); return response.json(); })
        .then(catalog => { state.catalog = catalog; renderStats(); renderApplications(); renderFilters(); renderFeatures(); })
        .catch(error => {
            statsEl.innerHTML = `<div class="card">No se pudo cargar el catálogo: ${escapeHtml(error.message)}</div>`;
            applicationsEl.innerHTML = '';
            featuresEl.innerHTML = '<tr><td colspan="9" class="empty">Catálogo no disponible.</td></tr>';
        });
})();
</script>
</body>
</html>
