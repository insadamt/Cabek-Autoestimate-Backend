<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_root_redirects_to_internal_statistics(): void
    {
        $response = $this->get('/');

        $response->assertRedirectToRoute('internal.statistics');
    }

    public function test_the_json_import_http_surface_is_not_exposed(): void
    {
        $this->get('/internal/ingest')->assertNotFound();
        $this->post('/internal/ingest/validate')->assertNotFound();
    }
}
