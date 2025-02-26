<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AreasTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('areas')->delete();
        
        \DB::table('areas')->insert(array (
            0 => 
            array (
                'id' => 120,
                'name' => 'Office 360',
                'address' => 'Las Vegas Blvd S, Las Vegas, NV 89109, United States',
                'created_at' => '2021-12-20 00:58:16',
                'updated_at' => '2021-12-20 01:47:46',
            ),
        ));
        
        
    }
}