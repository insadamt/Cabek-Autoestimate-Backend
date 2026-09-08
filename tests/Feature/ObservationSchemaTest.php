<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ObservationSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_part_price_observations_do_not_have_a_details_column(): void
    {
        $this->assertFalse(
            Schema::hasColumn('part_price_observations', 'details')
        );
    }
}
