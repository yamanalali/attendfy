<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ShiftUserTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('shift_user')->delete();
        
        \DB::table('shift_user')->insert(array (
            0 => 
            array (
                'id' => 1,
                'shift_id' => 1,
                'worker_id' => 7,
                'created_at' => '2023-06-03 21:10:19',
                'updated_at' => '2023-06-06 14:28:58',
            ),
            1 => 
            array (
                'id' => 2,
                'shift_id' => 2,
                'worker_id' => 8,
                'created_at' => '2023-06-03 21:30:36',
                'updated_at' => '2023-06-06 14:31:21',
            ),
        ));
        
        
    }
}