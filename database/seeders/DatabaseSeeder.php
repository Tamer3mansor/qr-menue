<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * The demo tenant is what the landing page links to, so it is seeded by
     * default. DemoSeeder checks the environment itself and refuses to run in
     * production.
     */
    public function run(): void
    {
        $this->call(DemoSeeder::class);
    }
}
