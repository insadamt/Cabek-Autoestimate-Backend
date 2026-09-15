<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's canonical reference data.
     */
    public function run(): void
    {
        $this->call([
            MarqueSeeder::class,
            ModelSeeder::class,
            PartSeeder::class,
            SourceSeeder::class,
        ]);
    }
}
