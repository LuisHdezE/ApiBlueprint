<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ApiBlueprint</title>
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, sans-serif; background: #f6f8fb; color: #132238; }
        * { box-sizing: border-box; }
        body { margin: 0; }
        main { max-width: 1120px; margin: 0 auto; padding: 72px 24px; }
        .eyebrow { color: #2563eb; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; font-size: 12px; }
        h1 { font-size: clamp(42px, 7vw, 74px); line-height: .95; margin: 18px 0; max-width: 850px; letter-spacing: -.05em; }
        p { color: #617087; line-height: 1.7; }
        .grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px; margin-top: 42px; }
        .card { background: white; border: 1px solid #e4eaf2; border-radius: 24px; padding: 24px; box-shadow: 0 18px 50px rgba(23, 42, 72, .06); }
        .card strong { display: block; font-size: 18px; margin-bottom: 8px; }
        .badge { display: inline-flex; border: 1px solid #dbe5f4; border-radius: 999px; padding: 8px 12px; margin-top: 22px; font-size: 13px; font-weight: 700; background: #f8fbff; }
        @media (max-width: 760px) { .grid { grid-template-columns: 1fr; } main { padding-top: 48px; } }
    </style>
</head>
<body>
<main>
    <div class="eyebrow">Eliasworks · API foundation</div>
    <h1>Design the API you actually need.</h1>
    <p>ApiBlueprint combines selectable capabilities, explicit endpoint exposure and a Laravel Clean Architecture foundation. The interactive configurator lands in the next commit of this baseline.</p>
    <section class="grid">
        <article class="card"><strong>Choose a template</strong><p>Start blank or from governed API profiles that remain fully editable.</p><span class="badge">Templates</span></article>
        <article class="card"><strong>Expose intentionally</strong><p>An endpoint is part of the exported contract only when the blueprint enables it.</p><span class="badge">Endpoint contract</span></article>
        <article class="card"><strong>Export cleanly</strong><p>Generate only the architecture required by the selected capabilities, not a dormant mega-project.</p><span class="badge">Solution export</span></article>
    </section>
</main>
</body>
</html>
