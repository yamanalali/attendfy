<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Event
 * 
 * @property int $id
 * @property string $title
 * @property string $desc
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @package App\Models\Base
 */
class Event extends Model
{
	protected $table = 'events';

	protected $dates = [
		'start_date',
		'end_date'
	];
}
