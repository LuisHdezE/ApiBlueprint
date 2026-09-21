<?php

namespace Tests\Feature;

use Tests\TestCase;

final class GovernedRuntimeTest extends TestCase
{
    public function test_api_responses_emit_correlation_id(): void
    {
        $this->withHeader('X-Correlation-ID', 'test-correlation-123')
            ->getJson('/api/v1/meta/status')
            ->assertOk()
            ->assertHeader('X-Correlation-ID', 'test-correlation-123');
    }

    public function test_unknown_api_route_uses_rfc_9457_problem_details_in_spanish(): void
    {
        $this->getJson('/api/v1/recurso-inexistente')
            ->assertStatus(404)
            ->assertHeader('content-type', 'application/problem+json')
            ->assertHeader('X-Correlation-ID')
            ->assertJsonPath('title', 'Recurso no encontrado')
            ->assertJsonPath('status', 404)
            ->assertJsonStructure(['type', 'title', 'status', 'detail', 'instance', 'correlation_id']);
    }
}
