from pathlib import Path

# Fix the generated controller heredoc: variables must be escaped once, not twice.
exporter = Path('app/Infrastructure/Blueprint/LaravelZipBlueprintExporter.php')
text = exporter.read_text()
start_marker = '    private function usersShowControllerFile(string $className): string\n'
end_marker = '    private function usersListControllerFile(string $className): string\n'
start = text.find(start_marker)
end = text.find(end_marker, start)
if start < 0 or end < 0:
    raise SystemExit('users.show controller method anchors not found')
method = r'''    private function usersShowControllerFile(string $className): string
    {
        return <<<PHP
<?php

namespace App\Presentation\Http\Controllers\Generated;

use App\Application\Users\UseCases\GetUser;
use App\Presentation\Http\Support\ProblemDetails;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class $className
{
    public function __construct(private GetUser \$getUser)
    {
        //
    }

    public function __invoke(Request \$request, string \$id): JsonResponse
    {
        \$user = \$this->getUser->execute(\$id);

        if (\$user === null) {
            return ProblemDetails::response(
                request: \$request,
                status: 404,
                title: 'Usuario no encontrado',
                detail: 'No existe un usuario con el identificador solicitado.',
                type: 'https://eliasworks.uy/problems/user-not-found',
            );
        }

        return response()->json(['data' => \$user->toArray()]);
    }
}
PHP;
    }

'''
exporter.write_text(text[:start] + method + text[end:])

# Move users.show response metadata out of parametersFor() and into responsesFor().
openapi = Path('app/Application/Blueprint/Queries/GetMasterOpenApi.php')
text = openapi.read_text()
separator = '    private function responsesFor(array $feature): array\n'
if separator not in text:
    raise SystemExit('responsesFor separator not found')
pre, post = text.split(separator, 1)
block_start = pre.find("        if ($feature['id'] === 'users.show') {\n")
list_marker = "        if (str_ends_with($feature['id'], '.list')) {\n"
block_end = pre.find(list_marker, block_start)
if block_start < 0 or block_end < 0:
    raise SystemExit('misplaced users.show response block not found')
block = pre[block_start:block_end]
pre = pre[:block_start] + pre[block_end:]
insert_at = post.find(list_marker)
if insert_at < 0:
    raise SystemExit('responsesFor generic list marker not found')
post = post[:insert_at] + block + post[insert_at:]
openapi.write_text(pre + separator + post)

print('U0.10 targeted fixes applied')
