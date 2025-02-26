<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ShiftsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('shifts')->delete();
        
        \DB::table('shifts')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'General Shift',
                'start_time' => '08:00:00',
                'end_time' => '16:00:00',
                'late_mark_after' => '00:05:00',
                'color' => 'chartreuse',
                'created_at' => '2023-06-03 10:53:17',
                'updated_at' => '2023-06-06 06:13:34',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'Night Shift',
                'start_time' => '16:00:00',
                'end_time' => '00:00:00',
                'late_mark_after' => '00:05:00',
                'color' => 'cyan',
                'created_at' => '2023-06-03 12:01:10',
                'updated_at' => '2023-06-06 06:13:28',
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'Day Shift',
                'start_time' => '13:00:00',
                'end_time' => '16:00:00',
                'late_mark_after' => '00:05:00',
                'color' => 'LightPink',
                'created_at' => '2023-06-03 12:14:40',
                'updated_at' => '2023-06-06 06:13:21',
            ),
        ));
        
        
    }
}