<?php

namespace App\Infrastructure\Blueprint\Recipes;

final class AuthLoginRecipe extends AbstractLaravelFeatureRecipe
{
    public function id(): string
    {
        return 'auth.login';
    }

    public function endpointIds(): array
    {
        return [
            'auth.login',
        ];
    }

    public function files(array $manifest): array
    {
        return [
            'database/database.sqlite' => '',
            'database/migrations/2026_01_01_000000_create_users_table.php' => $this->usersMigrationFile(),
            'database/migrations/2026_01_01_000001_create_personal_access_tokens_table.php' => $this->personalAccessTokensMigrationFile(),
            'app/Infrastructure/Identity/User.php' => $this->userModelFile(),
            'app/Application/Authentication/Data/AuthenticatedSession.php' => $this->authenticatedSessionFile(),
            'app/Application/Authentication/Contracts/AuthenticationGateway.php' => $this->authenticationGatewayContractFile(),
            'app/Application/Authentication/UseCases/LoginUser.php' => $this->loginUserUseCaseFile(),
            'app/Infrastructure/Authentication/SanctumAuthenticationGateway.php' => $this->sanctumAuthenticationGatewayFile(),
            'app/Presentation/Http/Support/LoginRequestValidator.php' => $this->loginRequestValidatorFile(),
            'tests/Feature/AuthLoginVerticalSliceTest.php' => $this->authLoginVerticalSliceTestFile(),
        ];
    }

    public function controller(array $endpoint, string $className): ?string
    {
        if (($endpoint['id'] ?? null) !== 'auth.login') {
            return null;
        }

        return $this->authLoginControllerFile($className);
    }

    public function serviceProviderImports(): array
    {
        return [
            'use App\\Application\\Authentication\\Contracts\\AuthenticationGateway;',
            'use App\\Infrastructure\\Authentication\\SanctumAuthenticationGateway;',
        ];
    }

    public function serviceProviderRegistrations(): array
    {
        return [
            '        $this->app->bind(AuthenticationGateway::class, SanctumAuthenticationGateway::class);',
        ];
    }

    public function composerRequireDev(): array
    {
        return [
            'mockery/mockery' => '^1.6',
        ];
    }

    private function authLoginControllerFile(string $className): string
    {
        return <<<PHP
<?php

namespace App\Presentation\Http\Controllers\Generated;

use App\Application\Authentication\UseCases\LoginUser;
use App\Presentation\Http\Support\LoginRequestValidator;
use App\Presentation\Http\Support\ProblemDetails;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class $className
{
    public function __construct(
        private LoginUser \$loginUser,
        private LoginRequestValidator \$validator,
    ) {
        //
    }

    public function __invoke(Request \$request): JsonResponse
    {
        \$credentials = \$this->validator->validate(\$request);
        \$session = \$this->loginUser->handle(
            email: \$credentials['email'],
            password: \$credentials['password'],
            tokenName: \$credentials['device_name'] ?? 'api-client',
        );

        if (\$session === null) {
            return ProblemDetails::response(
                request: \$request,
                status: 401,
                title: 'Credenciales inválidas',
                detail: 'El correo electrónico o la contraseña no son correctos.',
                type: 'https://eliasworks.uy/problems/invalid-credentials',
            );
        }

        return response()->json(['data' => \$session->toArray()]);
    }
}
PHP;
    }

    private function loginRequestValidatorFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Presentation\Http\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final class LoginRequestValidator
{
    public function validate(Request $request): array
    {
        return Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'device_name' => ['sometimes', 'string', 'max:100'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'device_name.max' => 'El nombre del dispositivo no puede superar 100 caracteres.',
        ])->validate();
    }
}
PHP;
    }

    private function authenticatedSessionFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Application\Authentication\Data;

final readonly class AuthenticatedSession
{
    public function __construct(
        public string $userId,
        public string $name,
        public string $email,
        public string $accessToken,
    ) {
        //
    }

    public function toArray(): array
    {
        return [
            'user' => [
                'id' => $this->userId,
                'name' => $this->name,
                'email' => $this->email,
            ],
            'access_token' => $this->accessToken,
            'token_type' => 'Bearer',
        ];
    }
}
PHP;
    }

    private function authenticationGatewayContractFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Application\Authentication\Contracts;

use App\Application\Authentication\Data\AuthenticatedSession;

interface AuthenticationGateway
{
    public function authenticate(string $email, string $password, string $tokenName): ?AuthenticatedSession;
}
PHP;
    }

    private function loginUserUseCaseFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Application\Authentication\UseCases;

use App\Application\Authentication\Contracts\AuthenticationGateway;
use App\Application\Authentication\Data\AuthenticatedSession;

final readonly class LoginUser
{
    public function __construct(private AuthenticationGateway $authentication)
    {
        //
    }

    public function handle(string $email, string $password, string $tokenName): ?AuthenticatedSession
    {
        return $this->authentication->authenticate(
            email: mb_strtolower(trim($email)),
            password: $password,
            tokenName: trim($tokenName) === '' ? 'api-client' : trim($tokenName),
        );
    }
}
PHP;
    }

    private function sanctumAuthenticationGatewayFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Infrastructure\Authentication;

use App\Application\Authentication\Contracts\AuthenticationGateway;
use App\Application\Authentication\Data\AuthenticatedSession;
use App\Infrastructure\Identity\User;
use Illuminate\Support\Facades\Hash;

final class SanctumAuthenticationGateway implements AuthenticationGateway
{
    public function authenticate(string $email, string $password, string $tokenName): ?AuthenticatedSession
    {
        $user = User::query()->where('email', $email)->first();
        if ($user === null || ! Hash::check($password, (string) $user->password)) {
            return null;
        }

        $token = $user->createToken($tokenName);

        return new AuthenticatedSession(
            userId: (string) $user->getKey(),
            name: (string) $user->name,
            email: (string) $user->email,
            accessToken: $token->plainTextToken,
        );
    }
}
PHP;
    }

    private function userModelFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Infrastructure\Identity;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

final class User extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
    ];
}
PHP;
    }

    private function usersMigrationFile(): string
    {
        return <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('user');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
PHP;
    }

    private function personalAccessTokensMigrationFile(): string
    {
        return <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
PHP;
    }

    private function authLoginVerticalSliceTestFile(): string
    {
        return <<<'PHP'
<?php

namespace Tests\Feature;

use App\Infrastructure\Identity\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

final class AuthLoginVerticalSliceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_receive_a_real_sanctum_token(): void
    {
        $user = User::query()->create([
            'name' => 'Usuario de prueba',
            'email' => 'user@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'USER@example.com',
            'password' => 'secret-password',
            'device_name' => 'integration-test',
        ])->assertOk()
            ->assertJsonPath('data.user.id', (string) $user->getKey())
            ->assertJsonPath('data.user.email', 'user@example.com')
            ->assertJsonPath('data.token_type', 'Bearer');

        $plainTextToken = $response->json('data.access_token');
        $this->assertIsString($plainTextToken);
        $this->assertNotSame('', $plainTextToken);

        $storedToken = PersonalAccessToken::findToken($plainTextToken);
        $this->assertNotNull($storedToken);
        $this->assertSame((string) $user->getKey(), (string) $storedToken->tokenable_id);
    }

    public function test_invalid_credentials_use_problem_details(): void
    {
        User::query()->create([
            'name' => 'Usuario de prueba',
            'email' => 'user@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(401)
            ->assertHeader('content-type', 'application/problem+json')
            ->assertJsonPath('title', 'Credenciales inválidas');
    }

    public function test_login_validates_required_credentials_in_spanish(): void
    {
        $this->postJson('/api/v1/auth/login', [])
            ->assertStatus(422)
            ->assertHeader('content-type', 'application/problem+json')
            ->assertJsonPath('title', 'Error de validación')
            ->assertJsonPath('errors.email.0', 'El correo electrónico es obligatorio.')
            ->assertJsonPath('errors.password.0', 'La contraseña es obligatoria.');
    }
}
PHP;
    }

}
