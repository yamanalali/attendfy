<?php

namespace App\Models;

use App\Models\Base\Setting as BaseSetting;

class Setting extends BaseSetting
{
	protected $fillable = [
		'app_name',
		'logo',
		'favicons',
		'color',
		'copyright',
		'key_app',
        'timezone'
    ];
}
