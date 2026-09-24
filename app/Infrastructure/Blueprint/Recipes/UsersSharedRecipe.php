<?php

namespace App\Infrastructure\Blueprint\Recipes;

final class UsersSharedRecipe extends AbstractLaravelFeatureRecipe
{
    public function id(): string
    {
        return 'support.users';
    }

    public function endpointIds(): array
    {
        return [
            'users.list',
            'users.show',
            'users.create',
        ];
    }

    public function files(array $manifest): array
    {
        return [
            'app/Application/Users/Data/UserData.php' => $this->userDataFile(),
        ];
    }

    private function userDataFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Application\Users\Data;

final readonly class UserData
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $role,
    ) {
        //
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
        ];
    }
}
PHP;
    }}
