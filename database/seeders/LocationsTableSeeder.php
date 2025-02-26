<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LocationsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('locations')->delete();
        
        \DB::table('locations')->insert(array (
            0 => 
            array (
                'id' => 918,
                'lat' => 36.130177,
                'longt' => -115.162621,
                'area_id' => 120,
            ),
            1 => 
            array (
                'id' => 919,
                'lat' => 36.129482,
                'longt' => -115.15284,
                'area_id' => 120,
            ),
            2 => 
            array (
                'id' => 920,
                'lat' => 36.120888,
                'longt' => -115.154037,
                'area_id' => 120,
            ),
            3 => 
            array (
                'id' => 921,
                'lat' => 36.123383,
                'longt' => -115.165543,
                'area_id' => 120,
            ),
        ));
        
        
    }
}