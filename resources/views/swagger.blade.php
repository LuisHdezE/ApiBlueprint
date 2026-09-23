<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Swagger · ApiBlueprint</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">
    <style>
        body{margin:0;background:#f6f8fb}.topbar-custom{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 20px;background:#17243a;color:white;font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.brand{font-weight:900}.links{display:flex;gap:8px;flex-wrap:wrap}.links a{color:#dbe7f6;text-decoration:none;border:1px solid #40506a;border-radius:9px;padding:7px 10px;font-size:12px;font-weight:700}.links a.active{background:white;color:#17243a;border-color:white}.notice{margin:14px 20px 0;padding:12px 14px;border:1px solid #bfdbfe;background:#eff6ff;border-radius:12px;color:#315174;font:12px/1.55 Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.swagger-ui .topbar{display:none}
    </style>
</head>
<body>
    <div class="topbar-custom">
        <div class="brand">ApiBlueprint · Swagger vivo</div>
        <div class="links"><a href="/">Compositor</a><a href="/catalogo">Catálogo</a><a class="active" href="/swagger">Swagger</a></div>
    </div>
    <div class="notice">Este Swagger representa la <strong>Master Feature Library</strong>: muestra únicamente features marcadas como implementadas y con contrato OpenAPI listo. La URL de ejecución corresponde a una solución generada, no al runtime administrativo de ApiBlueprint.</div>
    <div id="swagger-ui"></div>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
        window.onload = () => {
            SwaggerUIBundle({
                url: '/api/v1/blueprint/openapi',
                dom_id: '#swagger-ui',
                deepLinking: true,
                displayRequestDuration: true,
                persistAuthorization: true,
                tryItOutEnabled: false,
            });
        };
    </script>
</body>
</html>
