<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('users')->delete();
        
        \DB::table('users')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'Administrator',
                'email' => 'administrator@gmail.com',
                'email_verified_at' => NULL,
                'password' => '$2y$10$HuSU5aUWtaipXLvSPtYM8ewE1YhmV9B9EOmer4ECe/2AA.kNl4kY2',
                'remember_token' => 'hG9BqYCQ1rekYE2UpDFcmHZfusn7kS5po0oj6cjXqpLKD73T5Wr8cUwpvVBm',
                'image' => 'default-user.png',
                'role' => 1,
                'created_at' => '2020-03-25 01:37:36',
                'updated_at' => '2023-06-07 03:23:07',
            ),
            1 => 
            array (
                'id' => 7,
                'name' => 'Staff',
                'email' => 'staff@gmail.com',
                'email_verified_at' => NULL,
                'password' => '$2y$10$Pz0m6g7foKP/m4IMvYSGjOgOLxVUbHCYfugEPfCOheI4RqDWiF/CS',
                'remember_token' => '6Xp4h46iSwqfjcVtQVhZMfyCdBW5P8VkBA138UVt1aHxhqcsE9lIZSHBhIyc',
                'image' => 'default-user.png',
                'role' => 3,
                'created_at' => '2021-04-08 14:54:53',
                'updated_at' => '2021-12-20 00:49:28',
            ),
            2 => 
            array (
                'id' => 8,
                'name' => 'Admin',
                'email' => 'admin@gmail.com',
                'email_verified_at' => NULL,
                'password' => '$2y$10$KXrdFHLuNe3MnK/S2DzbsOQlqw5LTtbTDVguhGevHXWjwlUTpjQmu',
                'remember_token' => 'n9s3Qddhq8MCM4aymBvJjMU0xhft7fKeElgNCuddLJ4eRiPkgNU5hz3mMW9M',
                'image' => 'default-user.png',
                'role' => 2,
                'created_at' => '2021-04-08 15:09:09',
                'updated_at' => '2021-12-20 00:49:47',
            ),
        ));
        
        
    }
}