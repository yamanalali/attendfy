<?php

namespace App\Models;

use App\Models\Base\Event as BaseEvent;

class Event extends BaseEvent
{
	protected $fillable = [
		'title',
		'desc',
		'start_date',
		'end_date',
	];

	protected $casts = [
        'start_date'  => 'date:Y-m-d',
		'end_date' => 'date:Y-m-d',
    ];
}
