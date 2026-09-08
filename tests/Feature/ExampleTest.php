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

    public function test_the_json_import_screen_is_available(): void
    {
        $response = $this->get('/internal/ingest');

        $response
            ->assertOk()
            ->assertSee('JSON Import')
            ->assertSee('Validate only')
            ->assertSee('Import observations');
    }

    public function test_the_json_import_requires_a_file_or_pasted_payload(): void
    {
        $response = $this->post('/internal/ingest/validate');

        $response->assertSessionHasErrors('json_payload');
    }
}
