<?php

$features = [
    ['id' => 'auth.login', 'capability' => 'authentication', 'capability_label' => 'Autenticación', 'summary' => 'Iniciar sesión', 'method' => 'POST', 'path' => '/api/v1/auth/login', 'default_exposure' => 'public', 'implementation_status' => 'implemented', 'exportable' => true, 'openapi_ready' => true, 'tests_ready' => true],
    ['id' => 'auth.logout', 'capability' => 'authentication', 'capability_label' => 'Autenticación', 'summary' => 'Cerrar sesión', 'method' => 'POST', 'path' => '/api/v1/auth/logout', 'default_exposure' => 'authenticated'],
    ['id' => 'users.list', 'capability' => 'users', 'capability_label' => 'Usuarios', 'summary' => 'Listar usuarios', 'method' => 'GET', 'path' => '/api/v1/users', 'default_exposure' => 'admin'],
    ['id' => 'users.show', 'capability' => 'users', 'capability_label' => 'Usuarios', 'summary' => 'Obtener usuario', 'method' => 'GET', 'path' => '/api/v1/users/{id}', 'default_exposure' => 'admin'],
    ['id' => 'users.create', 'capability' => 'users', 'capability_label' => 'Usuarios', 'summary' => 'Crear usuario', 'method' => 'POST', 'path' => '/api/v1/users', 'default_exposure' => 'admin'],
    ['id' => 'users.update', 'capability' => 'users', 'capability_label' => 'Usuarios', 'summary' => 'Actualizar usuario', 'method' => 'PUT', 'path' => '/api/v1/users/{id}', 'default_exposure' => 'admin'],
    ['id' => 'users.delete', 'capability' => 'users', 'capability_label' => 'Usuarios', 'summary' => 'Eliminar usuario', 'method' => 'DELETE', 'path' => '/api/v1/users/{id}', 'default_exposure' => 'admin'],
    ['id' => 'roles.list', 'capability' => 'roles', 'capability_label' => 'Roles y permisos', 'summary' => 'Listar roles', 'method' => 'GET', 'path' => '/api/v1/roles', 'default_exposure' => 'admin'],
    ['id' => 'audit.list', 'capability' => 'audit', 'capability_label' => 'Auditoría', 'summary' => 'Listar eventos de auditoría', 'method' => 'GET', 'path' => '/api/v1/audit-events', 'default_exposure' => 'internal'],
    ['id' => 'customers.list', 'capability' => 'customers', 'capability_label' => 'Clientes', 'summary' => 'Listar clientes', 'method' => 'GET', 'path' => '/api/v1/customers', 'default_exposure' => 'authenticated'],
    ['id' => 'customers.show', 'capability' => 'customers', 'capability_label' => 'Clientes', 'summary' => 'Obtener cliente', 'method' => 'GET', 'path' => '/api/v1/customers/{id}', 'default_exposure' => 'authenticated'],
    ['id' => 'customers.create', 'capability' => 'customers', 'capability_label' => 'Clientes', 'summary' => 'Crear cliente', 'method' => 'POST', 'path' => '/api/v1/customers', 'default_exposure' => 'authenticated'],
    ['id' => 'customers.update', 'capability' => 'customers', 'capability_label' => 'Clientes', 'summary' => 'Actualizar cliente', 'method' => 'PUT', 'path' => '/api/v1/customers/{id}', 'default_exposure' => 'authenticated'],
    ['id' => 'customers.delete', 'capability' => 'customers', 'capability_label' => 'Clientes', 'summary' => 'Eliminar cliente', 'method' => 'DELETE', 'path' => '/api/v1/customers/{id}', 'default_exposure' => 'admin'],
    ['id' => 'products.list', 'capability' => 'products', 'capability_label' => 'Productos', 'summary' => 'Listar productos', 'method' => 'GET', 'path' => '/api/v1/products', 'default_exposure' => 'public', 'implementation_status' => 'implemented', 'exportable' => true, 'openapi_ready' => true, 'tests_ready' => true],
    ['id' => 'products.show', 'capability' => 'products', 'capability_label' => 'Productos', 'summary' => 'Obtener producto', 'method' => 'GET', 'path' => '/api/v1/products/{id}', 'default_exposure' => 'public', 'implementation_status' => 'implemented', 'exportable' => true, 'openapi_ready' => true, 'tests_ready' => true],
    ['id' => 'products.create', 'capability' => 'products', 'capability_label' => 'Productos', 'summary' => 'Crear producto', 'method' => 'POST', 'path' => '/api/v1/products', 'default_exposure' => 'admin'],
    ['id' => 'orders.list', 'capability' => 'orders', 'capability_label' => 'Pedidos', 'summary' => 'Listar pedidos', 'method' => 'GET', 'path' => '/api/v1/orders', 'default_exposure' => 'authenticated'],
    ['id' => 'orders.show', 'capability' => 'orders', 'capability_label' => 'Pedidos', 'summary' => 'Obtener pedido', 'method' => 'GET', 'path' => '/api/v1/orders/{id}', 'default_exposure' => 'authenticated'],
    ['id' => 'orders.create', 'capability' => 'orders', 'capability_label' => 'Pedidos', 'summary' => 'Crear pedido', 'method' => 'POST', 'path' => '/api/v1/orders', 'default_exposure' => 'authenticated'],
    ['id' => 'payments.create', 'capability' => 'payments', 'capability_label' => 'Pagos', 'summary' => 'Crear pago', 'method' => 'POST', 'path' => '/api/v1/payments', 'default_exposure' => 'authenticated'],
    ['id' => 'files.upload', 'capability' => 'files', 'capability_label' => 'Archivos', 'summary' => 'Subir archivo', 'method' => 'POST', 'path' => '/api/v1/files', 'default_exposure' => 'authenticated'],
    ['id' => 'webhooks.receive', 'capability' => 'webhooks', 'capability_label' => 'Webhooks', 'summary' => 'Recibir webhook', 'method' => 'POST', 'path' => '/api/v1/webhooks/{provider}', 'default_exposure' => 'public'],
];

foreach ($features as &$feature) {
    $feature['implementation_status'] ??= 'planned';
    $feature['exportable'] ??= false;
    $feature['openapi_ready'] ??= false;
    $feature['tests_ready'] ??= false;
}
unset($feature);

$applications = [
    [
        'id' => 'blank',
        'name' => 'API en blanco',
        'description' => 'Comienza desde una composición vacía y habilita solamente las funcionalidades que el producto necesita.',
        'category' => 'foundation',
        'features' => [],
    ],
    [
        'id' => 'crud',
        'name' => 'REST CRUD',
        'description' => 'Base CRUD reutilizable centrada inicialmente en clientes.',
        'category' => 'starter',
        'features' => ['customers.list', 'customers.show', 'customers.create', 'customers.update'],
    ],
    [
        'id' => 'saas',
        'name' => 'SaaS inicial',
        'description' => 'Autenticación, usuarios, roles y auditoría para una API SaaS gobernada.',
        'category' => 'saas',
        'features' => ['auth.login', 'auth.logout', 'users.list', 'users.show', 'users.create', 'users.update', 'roles.list', 'audit.list'],
    ],
    [
        'id' => 'commerce',
        'name' => 'Comercio inicial',
        'description' => 'Clientes, productos y pedidos con una superficie pública intencionalmente pequeña.',
        'category' => 'commerce',
        'features' => ['auth.login', 'customers.list', 'customers.show', 'customers.create', 'products.list', 'products.show', 'orders.list', 'orders.show', 'orders.create'],
    ],
];

$featureIndex = [];
foreach ($features as $feature) {
    $featureIndex[$feature['id']] = $feature;
}

foreach ($features as &$feature) {
    $feature['applications'] = array_values(array_map(
        static fn (array $application): string => $application['id'],
        array_filter(
            $applications,
            static fn (array $application): bool => in_array($feature['id'], $application['features'], true),
        ),
    ));
}
unset($feature);

$applications = array_map(static function (array $application) use ($featureIndex): array {
    $implemented = 0;
    foreach ($application['features'] as $featureId) {
        if (($featureIndex[$featureId]['implementation_status'] ?? 'planned') === 'implemented') {
            $implemented++;
        }
    }

    $total = count($application['features']);
    $status = $total === 0 || $implemented === $total
        ? 'ready'
        : ($implemented > 0 ? 'partial' : 'planned');

    return $application + [
        'status' => $status,
        'coverage' => [
            'implemented' => $implemented,
            'total' => $total,
        ],
    ];
}, $applications);

$templates = array_map(static fn (array $application): array => [
    'id' => $application['id'],
    'name' => $application['name'],
    'description' => $application['description'],
    'endpoints' => $application['features'],
    'status' => $application['status'],
    'coverage' => $application['coverage'],
], $applications);

$endpoints = array_map(static fn (array $feature): array => [
    'id' => $feature['id'],
    'capability' => $feature['capability'],
    'capability_label' => $feature['capability_label'],
    'summary' => $feature['summary'],
    'method' => $feature['method'],
    'path' => $feature['path'],
    'default_exposure' => $feature['default_exposure'],
    'implementation_status' => $feature['implementation_status'],
    'exportable' => $feature['exportable'],
    'openapi_ready' => $feature['openapi_ready'],
    'tests_ready' => $feature['tests_ready'],
    'applications' => $feature['applications'],
], $features);

return [
    'schema_version' => '0.3',
    'catalog_version' => '0.1',
    'api_version' => 'v1',
    'feature_statuses' => [
        ['id' => 'implemented', 'label' => 'Implementado'],
        ['id' => 'in_progress', 'label' => 'En desarrollo'],
        ['id' => 'planned', 'label' => 'Pendiente'],
    ],
    'application_statuses' => [
        ['id' => 'ready', 'label' => 'Disponible'],
        ['id' => 'partial', 'label' => 'Cobertura parcial'],
        ['id' => 'planned', 'label' => 'Planificado'],
    ],
    'exposures' => [
        ['id' => 'public', 'label' => 'Público'],
        ['id' => 'authenticated', 'label' => 'Autenticado'],
        ['id' => 'admin', 'label' => 'Administrador'],
        ['id' => 'internal', 'label' => 'Interno'],
    ],
    'governance' => [
        'authentication_strategies' => [
            ['id' => 'none', 'label' => 'Sin autenticación'],
            ['id' => 'sanctum', 'label' => 'Laravel Sanctum'],
        ],
        'pagination_strategies' => [
            ['id' => 'cursor', 'label' => 'Cursor'],
            ['id' => 'offset', 'label' => 'Offset'],
        ],
        'defaults' => [
            'authentication' => 'sanctum',
            'rbac' => true,
            'correlation_id' => true,
            'rate_limiting' => [
                'enabled' => true,
                'requests_per_minute' => 60,
            ],
            'pagination' => [
                'strategy' => 'cursor',
                'default_size' => 25,
                'max_size' => 100,
            ],
            'filtering' => true,
            'sorting' => true,
            'idempotency' => true,
            'audit' => true,
        ],
    ],
    'applications' => $applications,
    'features' => $features,
    'templates' => $templates,
    'exposure_dependencies' => [
        'authenticated' => ['auth.login'],
        'admin' => ['auth.login'],
        'internal' => ['auth.login'],
    ],
    'endpoint_dependencies' => [
        'auth.logout' => ['auth.login'],
        'payments.create' => ['orders.show'],
    ],
    'endpoints' => $endpoints,
];
