<?php

namespace App\Infrastructure\Blueprint\Recipes;

final class ListQuerySupportRecipe extends AbstractLaravelFeatureRecipe
{
    public function id(): string
    {
        return 'support.list-query';
    }

    public function endpointIds(): array
    {
        return [
            'users.list',
            'products.list',
        ];
    }

    public function files(array $manifest): array
    {
        return [
            'app/Application/Shared/Query/QueryOptions.php' => $this->queryOptionsFile(),
            'app/Presentation/Http/Support/QueryOptionsParser.php' => $this->queryOptionsParserFile($manifest),
            'app/Infrastructure/Database/DatabaseQueryPaginator.php' => $this->databaseQueryPaginatorFile(),
            'app/Presentation/Http/Support/ListQueryValidator.php' => $this->listQueryValidatorFile($manifest),
        ];
    }

    private function queryOptionsFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Application\Shared\Query;

final readonly class QueryOptions
{
    public function __construct(
        public string $paginationStrategy,
        public int $pageSize,
        public ?string $cursor,
        public int $pageNumber,
        public array $filters,
        public ?string $sort,
    ) {
        //
    }
}
PHP;
    }

    private function queryOptionsParserFile(array $manifest): string
    {
        $pagination = $manifest['governance']['pagination'];
        $strategy = var_export($pagination['strategy'], true);
        $defaultSize = $pagination['default_size'];
        $maxSize = $pagination['max_size'];
        $filtering = $manifest['governance']['filtering'] ? 'true' : 'false';
        $sorting = $manifest['governance']['sorting'] ? 'true' : 'false';

        return <<<PHP
<?php

namespace App\Presentation\Http\Support;

use App\Application\Shared\Query\QueryOptions;
use Illuminate\Http\Request;

final class QueryOptionsParser
{
    public function parse(Request \$request): QueryOptions
    {
        \$page = is_array(\$request->query('page')) ? \$request->query('page') : [];
        \$pageSize = min(max((int) (\$page['size'] ?? $defaultSize), 1), $maxSize);
        \$pageNumber = max((int) (\$page['number'] ?? 1), 1);
        \$cursor = isset(\$page['cursor']) ? (string) \$page['cursor'] : null;
        \$filters = $filtering && is_array(\$request->query('filter')) ? \$request->query('filter') : [];
        \$sort = $sorting && is_string(\$request->query('sort')) ? \$request->query('sort') : null;

        return new QueryOptions($strategy, \$pageSize, \$cursor, \$pageNumber, \$filters, \$sort);
    }
}
PHP;
    }

    private function databaseQueryPaginatorFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Infrastructure\Database;

use App\Application\Shared\Query\QueryOptions;
use Illuminate\Database\Query\Builder;
use InvalidArgumentException;
use JsonException;

final class DatabaseQueryPaginator
{
    public function sorts(?string $sort, array $allowedFields): array
    {
        if (! in_array('id', $allowedFields, true)) {
            throw new InvalidArgumentException('Stable lists require id as a tie breaker.');
        }

        $tokens = $sort === null || trim($sort) === '' ? ['id'] : explode(',', $sort);
        $sorts = [];
        $seen = [];

        foreach ($tokens as $token) {
            $token = trim($token);
            $direction = str_starts_with($token, '-') ? 'desc' : 'asc';
            $field = ltrim($token, '-');
            if (! in_array($field, $allowedFields, true) || isset($seen[$field])) {
                throw new InvalidArgumentException('Unsupported sort definition.');
            }
            $seen[$field] = true;
            $sorts[] = [$field, $direction];
        }

        if (! isset($seen['id'])) {
            $sorts[] = ['id', 'asc'];
        }

        return $sorts;
    }

    public function paginate(Builder $query, array $sorts, QueryOptions $options): array
    {
        return match ($options->paginationStrategy) {
            'cursor' => $this->cursorPage($query, $sorts, $options),
            'offset' => $this->offsetPage($query, $sorts, $options),
            default => throw new InvalidArgumentException('Unsupported pagination strategy.'),
        };
    }

    private function offsetPage(Builder $query, array $sorts, QueryOptions $options): array
    {
        $total = (clone $query)->count();
        $this->applySorts($query, $sorts);
        $rows = $query
            ->offset(($options->pageNumber - 1) * $options->pageSize)
            ->limit($options->pageSize)
            ->get();
        $totalPages = $total === 0 ? 0 : (int) ceil($total / $options->pageSize);

        return [
            'items' => $rows->all(),
            'meta' => [
                'strategy' => 'offset',
                'page_size' => $options->pageSize,
                'page_number' => $options->pageNumber,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_more' => $options->pageNumber < $totalPages,
            ],
        ];
    }

    private function cursorPage(Builder $query, array $sorts, QueryOptions $options): array
    {
        if ($options->cursor !== null && $options->cursor !== '') {
            $values = $this->decodeCursor($options->cursor, $sorts);
            $this->applyCursor($query, $sorts, $values);
        }

        $this->applySorts($query, $sorts);
        $rows = $query->limit($options->pageSize + 1)->get();
        $hasMore = $rows->count() > $options->pageSize;
        $visibleRows = $rows->take($options->pageSize)->values();
        $lastRow = $visibleRows->last();
        $nextCursor = $hasMore && is_object($lastRow) ? $this->encodeCursor($sorts, $lastRow) : null;

        return [
            'items' => $visibleRows->all(),
            'meta' => [
                'strategy' => 'cursor',
                'page_size' => $options->pageSize,
                'has_more' => $hasMore,
                'next_cursor' => $nextCursor,
            ],
        ];
    }

    private function applySorts(Builder $query, array $sorts): void
    {
        foreach ($sorts as [$field, $direction]) {
            $query->orderBy($field, $direction);
        }
    }

    private function applyCursor(Builder $query, array $sorts, array $values): void
    {
        $query->where(function (Builder $outer) use ($sorts, $values): void {
            foreach ($sorts as $index => [$field, $direction]) {
                $outer->orWhere(function (Builder $branch) use ($sorts, $values, $index, $field, $direction): void {
                    for ($previous = 0; $previous < $index; $previous++) {
                        [$previousField] = $sorts[$previous];
                        $branch->where($previousField, '=', $values[$previousField]);
                    }
                    $branch->where($field, $direction === 'asc' ? '>' : '<', $values[$field]);
                });
            }
        });
    }

    private function encodeCursor(array $sorts, object $row): string
    {
        $values = [];
        foreach ($sorts as [$field]) {
            $values[$field] = (string) $row->{$field};
        }
        $payload = json_encode([
            'sort' => $this->serializedSorts($sorts),
            'values' => $values,
        ], JSON_THROW_ON_ERROR);

        return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    }

    private function decodeCursor(string $cursor, array $sorts): array
    {
        try {
            $base64 = strtr($cursor, '-_', '+/');
            $base64 .= str_repeat('=', (4 - strlen($base64) % 4) % 4);
            $decoded = base64_decode($base64, true);
            if ($decoded === false) {
                throw new InvalidArgumentException('Invalid cursor encoding.');
            }
            $payload = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException('Invalid cursor payload.');
        }

        if (! is_array($payload)
            || ($payload['sort'] ?? null) !== $this->serializedSorts($sorts)
            || ! is_array($payload['values'] ?? null)) {
            throw new InvalidArgumentException('Cursor does not match sorting.');
        }

        $values = [];
        foreach ($sorts as [$field]) {
            $value = $payload['values'][$field] ?? null;
            if (! is_string($value)) {
                throw new InvalidArgumentException('Cursor is missing sort values.');
            }
            $values[$field] = $value;
        }

        return $values;
    }

    private function serializedSorts(array $sorts): array
    {
        return array_map(static fn (array $sort): string => ($sort[1] === 'desc' ? '-' : '').$sort[0], $sorts);
    }
}
PHP;
    }

    private function listQueryValidatorFile(array $manifest): string
    {
        $pagination = $manifest['governance']['pagination'];
        $maxSize = (int) $pagination['max_size'];
        $strategy = $pagination['strategy'];
        $pageRule = $strategy === 'cursor' ? 'array:size,cursor' : 'array:size,number';
        $strategyRule = $strategy === 'cursor'
            ? "            'page.cursor' => ['sometimes', 'string', 'max:2048'],"
            : "            'page.number' => ['sometimes', 'integer', 'min:1'],";
        $filtering = $manifest['governance']['filtering'] ? 'true' : 'false';
        $sorting = $manifest['governance']['sorting'] ? 'true' : 'false';

        return <<<PHP
<?php

namespace App\Presentation\Http\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final class ListQueryValidator
{
    public function validate(Request \$request, array \$filterFields, array \$sortFields): void
    {
        \$rules = [
            'page' => ['sometimes', '$pageRule'],
            'page.size' => ['sometimes', 'integer', 'min:1', 'max:$maxSize'],
$strategyRule
        ];

        if ($filtering) {
            \$rules['filter'] = ['sometimes', 'array:'.implode(',', \$filterFields)];
            foreach (\$filterFields as \$field) {
                \$rules['filter.'.\$field] = ['sometimes', 'string', 'max:255'];
            }
        } else {
            \$rules['filter'] = ['prohibited'];
        }

        \$rules['sort'] = $sorting ? ['sometimes', 'string', 'max:255'] : ['prohibited'];

        \$validator = Validator::make(\$request->query(), \$rules, [
            'page.array' => 'Los parámetros de paginación no son válidos para la estrategia configurada.',
            'page.size.integer' => 'page[size] debe ser un entero.',
            'page.size.min' => 'page[size] debe ser mayor que cero.',
            'page.size.max' => 'page[size] supera el máximo permitido.',
            'page.number.integer' => 'page[number] debe ser un entero.',
            'page.number.min' => 'page[number] debe ser mayor que cero.',
            'filter.array' => 'filter contiene campos no permitidos para este listado.',
            'filter.prohibited' => 'Los filtros están deshabilitados para este blueprint.',
            'sort.prohibited' => 'El ordenamiento está deshabilitado para este blueprint.',
        ]);

        if ($sorting) {
            \$validator->after(function (\$validator) use (\$request, \$sortFields): void {
                \$sort = \$request->query('sort');
                if (\$sort === null || \$sort === '') {
                    return;
                }
                if (! is_string(\$sort)) {
                    \$validator->errors()->add('sort', 'El parámetro sort debe ser una cadena.');

                    return;
                }

                \$seen = [];
                foreach (explode(',', \$sort) as \$token) {
                    \$token = trim(\$token);
                    \$field = ltrim(\$token, '-');
                    if (\$token === '' || ! in_array(\$field, \$sortFields, true) || isset(\$seen[\$field])) {
                        \$validator->errors()->add('sort', 'sort contiene campos no permitidos o repetidos.');

                        return;
                    }
                    \$seen[\$field] = true;
                }
            });
        }

        \$validator->validate();
    }
}
PHP;
    }}
