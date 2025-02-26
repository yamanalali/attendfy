<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SettingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('settings')->delete();
        
        \DB::table('settings')->insert(array (
            0 => 
            array (
                'id' => 1,
                'app_name' => 'Attendance FR',
                'logo' => 'logo.png',
                'favicons' => 'favicon-96x96.png',
                'color' => 'blue',
                'copyright' => 'MuliaTech',
                'key_app' => 'jGJ7fsHVNlTDUWGVVBmTRZeBNiIqeZgozcC6Uy7C',
                'timezone' => 'Asia/Hong_Kong',
                'created_at' => '2021-04-08 21:48:26',
                'updated_at' => '2023-06-07 05:40:20',
            ),
        ));
        
        
    }
}