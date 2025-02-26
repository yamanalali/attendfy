<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Shift
 * 
 * @property int $id
 * @property string $name
 * @property Carbon $start_time
 * @property Carbon $end_time
 * @property Carbon $late_mark_after
 * @property string $color
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * 
 * @property Collection|User[] $users
 *
 * @package App\Models\Base
 */
class Shift extends Model
{
	protected $table = 'shifts';

	protected $dates = [
		'start_time',
		'end_time',
		'late_mark_after'
	];

	public function users()
	{
		return $this->belongsToMany(User::class, 'shift_user', 'shift_id', 'worker_id')
					->withPivot('id')
					->withTimestamps();
	}
}
