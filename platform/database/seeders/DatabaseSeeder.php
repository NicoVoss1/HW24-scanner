<?php
declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/** No default test users or automatic JTL source imports in production. */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Nothing by default. The explicit metadata-only seeder is:
        // php artisan db:seed --class=ObservedSourcesSeeder
        // Real admin accounts are created securely via Filament's CLI.
    }
}
