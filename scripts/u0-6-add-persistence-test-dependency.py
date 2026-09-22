from pathlib import Path

path = Path('app/Infrastructure/Blueprint/LaravelZipBlueprintExporter.php')
source = path.read_text()

old = """        return json_encode([\n            '$schema' => 'https://getcomposer.org/schema.json',\n            'name' => 'generated/'.$this->slug((string) $manifest['project']['name']),\n            'type' => 'project',\n            'description' => 'API Laravel generada por ApiBlueprint.',\n            'license' => 'proprietary',\n            'require' => $require,\n            'require-dev' => [\n                'laravel/pint' => '^1.27',\n                'nunomaduro/collision' => '^8.6',\n                'phpunit/phpunit' => '^12.5',\n            ],\n"""
new = """        $requireDev = [\n            'laravel/pint' => '^1.27',\n            'nunomaduro/collision' => '^8.6',\n            'phpunit/phpunit' => '^12.5',\n        ];\n\n        if ($this->hasEndpoint($manifest, 'products.show')) {\n            $requireDev['mockery/mockery'] = '^1.6';\n        }\n\n        return json_encode([\n            '$schema' => 'https://getcomposer.org/schema.json',\n            'name' => 'generated/'.$this->slug((string) $manifest['project']['name']),\n            'type' => 'project',\n            'description' => 'API Laravel generada por ApiBlueprint.',\n            'license' => 'proprietary',\n            'require' => $require,\n            'require-dev' => $requireDev,\n"""

if source.count(old) != 1:
    raise SystemExit(f'Expected one composer require-dev block, got {source.count(old)}')

path.write_text(source.replace(old, new, 1))
