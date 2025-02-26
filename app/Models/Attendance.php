<?php

namespace App\Models;

use App\Models\Base\Attendance as BaseAttendance;

class Attendance extends BaseAttendance
{
	protected $fillable = [
		'worker_id',
		'date',
		'date_out',
		'in_time',
		'out_time',
		'work_hour',
		'over_time',
		'late_time',
		'early_out_time',
		'in_location_id',
		'out_location_id'
	];

    protected $dates = [
        'date',
		'date_out'
    ];

    protected $casts = [
        'date'  => 'date:Y-m-d',
		'date_out' => 'date:Y-m-d',
    ];

	public function areaOut()
	{
		return $this->belongsTo(Area::class, 'out_location_id');
	}

	public function areaIn()
	{
		return $this->belongsTo(Area::class, 'in_location_id');
	}
}
