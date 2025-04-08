<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        $user = new User();
        $user->name = "Administrador";
        $user->email = "admin@gmail.com";
        $user->password = Hash::make("admin1234");
        $user->rol = "admin";
        $user->email_verified_at = "2025-03-25";

        $user->save();

        User::factory()->count(10)->create(); // Crea 10 usuarios
    }
}
