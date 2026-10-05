<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Bootstraps the very first super admin.
 *
 * Set SUPER_ADMIN_EMAIL and SUPER_ADMIN_PASSWORD in your environment file and
 * run `php artisan db:seed --class=SuperAdminSeeder`. An existing account with
 * the same email is promoted rather than duplicated.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('SUPER_ADMIN_EMAIL');

        if ($email === '') {
            $this->command?->warn('SUPER_ADMIN_EMAIL is not set, no super admin was created.');

            return;
        }

        $admin = User::query()->firstOrNew(['email' => $email]);

        $admin->fill([
            'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
            'password' => Hash::make((string) env('SUPER_ADMIN_PASSWORD', 'password')),
        ]);

        // Assigned directly because is_super_admin is deliberately not fillable.
        $admin->is_super_admin = true;
        $admin->save();

        $this->command?->info("Super admin ready: {$admin->email}");
    }
}
