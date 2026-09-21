<?php

namespace Tests\Feature;

use Tests\TestCase;

final class LandingPageTest extends TestCase
{
    public function test_landing_page_is_available_in_spanish_and_exposes_zip_export(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('ApiBlueprint')
            ->assertSee('Diseña la API antes de que la API diseñe tu proyecto.')
            ->assertSee('Exportar solución ZIP');
    }
}
