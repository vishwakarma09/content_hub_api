<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\FileFolder;
use App\Models\FileMetadata;
use App\Models\FileFolderShare;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // create users
        $userList = [
            [
                'name' => 'user1',
                'email' => 'user1@example.com',
            ],
            [
                'name' => 'user2',
                'email' => 'user2@example.com',
            ],
            [
                'name' => 'user3',
                'email' => 'user3@example.com',
            ],
            [
                'name' => 'user4',
                'email' => 'user4@example.com',
            ],
        ];
        foreach ($userList as $user) {
            User::factory()->create($user);
        }
    }
}
