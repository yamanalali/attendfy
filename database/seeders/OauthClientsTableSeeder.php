<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class OauthClientsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('oauth_clients')->delete();
        
        \DB::table('oauth_clients')->insert(array (
            0 => 
            array (
                'id' => 1,
                'user_id' => NULL,
                'name' => 'Attendance Fingerprint Personal Access Client',
                'secret' => 'CKwXiB5WgAkfIYQFhwktZoygtyEIubnaCaKKPBqB',
                'redirect' => 'http://localhost',
                'personal_access_client' => 1,
                'password_client' => 0,
                'revoked' => 0,
                'created_at' => '2021-04-14 12:54:18',
                'updated_at' => '2021-04-14 12:54:18',
            ),
            1 => 
            array (
                'id' => 2,
                'user_id' => NULL,
                'name' => 'Attendance Fingerprint Password Grant Client',
                'secret' => 'xNEP33rB1XEWW1cwkE6bKBdQ7kvgfkplywAVr900',
                'redirect' => 'http://localhost',
                'personal_access_client' => 0,
                'password_client' => 1,
                'revoked' => 0,
                'created_at' => '2021-04-14 12:54:18',
                'updated_at' => '2021-04-14 12:54:18',
            ),
        ));
        
        
    }
}