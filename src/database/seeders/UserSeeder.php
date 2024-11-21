<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name'     => 'John Doe',
            'email'    => 'johndoe@example.com',
            'password' => Hash::make('Password!786#'),
        ]);

        User::create([
            'name'     => 'Jane Smith',
            'email'    => 'janesmith@example.com',
            'password' => Hash::make('Aq2#21d'),
        ]);
    }
}
