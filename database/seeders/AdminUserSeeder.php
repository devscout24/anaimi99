<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Users
        $users = [
            [
                'name' => 'Admin',
                'username' => 'admin',
                'email' => 'admin@admin.com',
                'password' => Hash::make('12345678'),
                'role' => 1,
            ],
            [
                'name' => 'User',
                'username' => 'user',
                'email' => 'user@user.com',
                'password' => Hash::make('12345678'),
            ],
            [
                'name' => 'sajib',
                'username' => 'sajib',
                'email' => 'sajib@gmail.com',
                'password' => Hash::make('12345678'),
            ]
        ];

        foreach ($users as $user) {
            $userModel = User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'password' => $user['password'],
                ]
            );

            if (array_key_exists('username', $user)) {
                $userModel->username = $user['username'];
            }

            if (array_key_exists('role', $user)) {
                $userModel->role = $user['role'];
            }

            $userModel->save();
        }
    }
}
