<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Deliberately does NOT seed any User: the dev-only admin account is
     * created via a one-off artisan command (see docs/lote-2/00-contrato-datos.md
     * and the README note), not by a seeder — a seeder that creates a user
     * with a predictable password is exactly the kind of thing that has
     * leaked into production before.
     */
    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            DemoTourSeeder::class,
        ]);
    }
}
