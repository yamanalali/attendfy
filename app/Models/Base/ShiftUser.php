<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ShiftUser
 * 
 * @property int $id
 * @property int $shift_id
 * @property int $worker_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * 
 * @property Shift $shift
 * @property User $user
 *
 * @package App\Models\Base
 */
class ShiftUser extends Model
{
	protected $table = 'shift_user';

	protected $casts = [
		'shift_id' => 'int',
		'worker_id' => 'int'
	];

	public function shift()
	{
		return $this->belongsTo(Shift::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class, 'worker_id');
	}
}
