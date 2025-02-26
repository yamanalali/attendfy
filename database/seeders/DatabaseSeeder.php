<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        //
        $this->call(AreasTableSeeder::class);
        $this->call(LocationsTableSeeder::class);
        $this->call(OauthClientsTableSeeder::class);
        $this->call(OauthPersonalAccessClientsTableSeeder::class);
        $this->call(RolesTableSeeder::class);
        $this->call(RoleUserTableSeeder::class);
        $this->call(SettingsTableSeeder::class);
        $this->call(UsersTableSeeder::class);
        $this->call(ShiftsTableSeeder::class);
        $this->call(ShiftUserTableSeeder::class);
    }
}