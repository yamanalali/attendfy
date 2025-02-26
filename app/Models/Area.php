<?php

namespace App\Models;

use App\Models\Base\Area as BaseArea;

class Area extends BaseArea
{
    protected $fillable = [
        'name',
        'address',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => 'date:Y-m-d',
        'updated_at' => 'date:Y-m-d',
    ];
}
