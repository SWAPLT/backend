<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\UserSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);
        $this->call(CategoriaSeeder::class);
        $this->call(VehiculoSeeder::class);
        $this->call(FavoritoSeeder::class);
        $this->call(MensajeSeeder::class);
    }
}
