<?php

namespace App\Models;

use App\Models\Base\Shift as BaseShift;

class Shift extends BaseShift
{
	protected $fillable = [
		'name',
		'start_time',
		'end_time',
		'late_mark_after',
		'color'
	];

	protected $casts = [
        'start_time'  => 'string',
		'end_time'  => 'string',
		'late_mark_after'  => 'string',
    ];
}
