<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email    = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');
        $name     = env('ADMIN_NAME', 'Admin');

        // No ADMIN_EMAIL set? Treat as "seeding intentionally disabled" (local dev, CI, etc.)
        if (empty($email)) {
            $this->command?->warn('[AdminUserSeeder] ADMIN_EMAIL not set — skipping admin user seed.');
            return;
        }

        // ADMIN_EMAIL is set but ADMIN_PASSWORD is missing → fail loud, don't silently create a
        // blank-password account.
        if (empty($password)) {
            throw new RuntimeException(
                'AdminUserSeeder: ADMIN_EMAIL is set but ADMIN_PASSWORD is missing. '
                .'Set ADMIN_PASSWORD in your environment (e.g. Coolify → Environment Variables).'
            );
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name'              => $name,
                'password'          => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );

        $this->command?->info("[AdminUserSeeder] Admin user synced: {$email}");
    }
}
