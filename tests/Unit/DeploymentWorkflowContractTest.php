<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class DeploymentWorkflowContractTest extends TestCase
{
    public function test_production_deployment_requires_explicit_promotion_branch(): void
    {
        $workflow = $this->workflow();

        $this->assertStringContainsString('branches: [deploy/production]', $workflow);
        $this->assertStringNotContainsString('branches: [main]', $workflow);
        $this->assertStringContainsString('git merge-base --is-ancestor "$GITHUB_SHA" origin/main', $workflow);
    }

    public function test_production_deployment_is_reproducible_and_smoked(): void
    {
        $workflow = $this->workflow();

        $this->assertStringContainsString('test -f composer.lock', $workflow);
        $this->assertStringContainsString('composer validate --strict', $workflow);
        $this->assertStringContainsString('php artisan test', $workflow);
        $this->assertStringContainsString('/api/v1/meta/status', $workflow);
        $this->assertStringContainsString('/api/v1/blueprint/export', $workflow);
        $this->assertStringContainsString('/release.json', $workflow);
    }

    public function test_ftp_action_is_pinned_and_server_environment_is_not_overwritten(): void
    {
        $workflow = $this->workflow();

        $this->assertStringContainsString(
            'SamKirkland/FTP-Deploy-Action@110f9186c050f71550953127052e77650219c287',
            $workflow,
        );
        $this->assertStringContainsString('dangerous-clean-slate: false', $workflow);
        $this->assertStringContainsString('APIBLUEPRINT_FTP_SERVER_DIR', $workflow);
        $this->assertStringContainsString('APIBLUEPRINT_PUBLIC_URL', $workflow);
        $this->assertStringContainsString("--exclude='.env'", $workflow);
    }

    private function workflow(): string
    {
        $path = dirname(__DIR__, 2).'/.github/workflows/deploy-production.yml';
        $workflow = file_get_contents($path);

        $this->assertIsString($workflow);

        return $workflow;
    }
}
