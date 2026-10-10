<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * The super admin is upserted from SUPER_ADMIN_* environment variables so it
     * is created or updated on every deploy. The demo tenant is what the landing
     * page links to, so it is seeded next. DemoSeeder checks the environment
     * itself and refuses to run in production.
     */
    public function run(): void
    {
        $this->call(SuperAdminSeeder::class);
        $this->call(DemoSeeder::class);
    }
}
