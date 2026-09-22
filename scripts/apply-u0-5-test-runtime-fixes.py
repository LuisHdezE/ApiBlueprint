#!/usr/bin/env python3

from pathlib import Path

path = Path('app/Infrastructure/Blueprint/LaravelZipBlueprintExporter.php')
text = path.read_text(encoding='utf-8')

require_dev_old = """            'require-dev' => [
                'laravel/pint' => '^1.27',
                'phpunit/phpunit' => '^12.5',
            ],"""
require_dev_new = """            'require-dev' => [
                'laravel/pint' => '^1.27',
                'nunomaduro/collision' => '^8.6',
                'phpunit/phpunit' => '^12.5',
            ],"""
if text.count(require_dev_old) != 1:
    raise SystemExit('Expected generated require-dev block exactly once.')
text = text.replace(require_dev_old, require_dev_new, 1)

throwable_import = "            'use Throwable;',\n"
if text.count(throwable_import) != 1:
    raise SystemExit('Expected Throwable import exactly once.')
text = text.replace(throwable_import, '', 1)

path.write_text(text, encoding='utf-8')
print('U0.5 generated test runtime fixes applied.')
