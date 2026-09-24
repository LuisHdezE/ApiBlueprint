<?php

namespace App\Infrastructure\Blueprint\Recipes;

final class AuthLogoutRecipe extends AbstractLaravelFeatureRecipe
{
    public function id(): string
    {
        return 'auth.logout';
    }

    public function endpointIds(): array
    {
        return [
            'auth.logout',
        ];
    }

    public function files(array $manifest): array
    {
        return [
            'app/Application/Authentication/Contracts/TokenRevocationGateway.php' => $this->tokenRevocationGatewayContractFile(),
            'app/Application/Authentication/UseCases/LogoutUser.php' => $this->logoutUserUseCaseFile(),
            'app/Infrastructure/Authentication/SanctumTokenRevocationGateway.php' => $this->sanctumTokenRevocationGatewayFile(),
            'tests/Feature/AuthLogoutVerticalSliceTest.php' => $this->authLogoutVerticalSliceTestFile(),
        ];
    }

    public function controller(array $endpoint, string $className): ?string
    {
        if (($endpoint['id'] ?? null) !== 'auth.logout') {
            return null;
        }

        return $this->authLogoutControllerFile($className);
    }

    public function serviceProviderImports(): array
    {
        return [
            'use App\\Application\\Authentication\\Contracts\\TokenRevocationGateway;',
            'use App\\Infrastructure\\Authentication\\SanctumTokenRevocationGateway;',
        ];
    }

    public function serviceProviderRegistrations(): array
    {
        return [
            '        $this->app->bind(TokenRevocationGateway::class, SanctumTokenRevocationGateway::class);',
        ];
    }

    private function authLogoutControllerFile(string $className): string
    {
        return <<<PHP
<?php

namespace App\Presentation\Http\Controllers\Generated;

use App\Application\Authentication\UseCases\LogoutUser;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class $className
{
    public function __construct(private LogoutUser \$logoutUser)
    {
        //
    }

    public function __invoke(Request \$request): Response
    {
        \$token = (string) \$request->bearerToken();
        \$this->logoutUser->handle(\$token);

        return response()->noContent();
    }
}
PHP;
    }

    private function tokenRevocationGatewayContractFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Application\Authentication\Contracts;

interface TokenRevocationGateway
{
    public function revoke(string $plainTextToken): bool;
}
PHP;
    }

    private function logoutUserUseCaseFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Application\Authentication\UseCases;

use App\Application\Authentication\Contracts\TokenRevocationGateway;

final readonly class LogoutUser
{
    public function __construct(private TokenRevocationGateway $tokens)
    {
        //
    }

    public function handle(string $plainTextToken): bool
    {
        return $plainTextToken !== '' && $this->tokens->revoke($plainTextToken);
    }
}
PHP;
    }

    private function sanctumTokenRevocationGatewayFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Infrastructure\Authentication;

use App\Application\Authentication\Contracts\TokenRevocationGateway;
use Laravel\Sanctum\PersonalAccessToken;

final class SanctumTokenRevocationGateway implements TokenRevocationGateway
{
    public function revoke(string $plainTextToken): bool
    {
        $token = PersonalAccessToken::findToken($plainTextToken);
        if ($token === null) {
            return false;
        }

        return (bool) $token->delete();
    }
}
PHP;
    }

    private function authLogoutVerticalSliceTestFile(): string
    {
        return <<<'PHP'
<?php

namespace Tests\Feature;

use App\Infrastructure\Identity\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

final class AuthLogoutVerticalSliceTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_sanctum_token_is_revoked_without_affecting_the_authentication_model(): void
    {
        $user = User::query()->create([
            'name' => 'Usuario de prueba',
            'email' => 'logout@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'logout@example.com',
            'password' => 'secret-password',
            'device_name' => 'logout-test',
        ])->assertOk();

        $plainTextToken = $login->json('data.access_token');
        $this->assertIsString($plainTextToken);
        $this->assertNotNull(PersonalAccessToken::findToken($plainTextToken));

        $this->withToken($plainTextToken)
            ->postJson('/api/v1/auth/logout')
            ->assertNoContent();

        $this->assertNull(PersonalAccessToken::findToken($plainTextToken));
        $this->assertSame((string) $user->getKey(), (string) User::query()->findOrFail($user->getKey())->getKey());

        app('auth')->forgetGuards();

        $this->withToken($plainTextToken)
            ->postJson('/api/v1/auth/logout')
            ->assertUnauthorized();
    }
}
PHP;
    }}
