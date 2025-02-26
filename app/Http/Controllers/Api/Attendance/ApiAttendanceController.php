<?php

namespace App\Http\Controllers\Api\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Attendance;
use App\Models\Location;
use App\Models\Setting;
use App\Models\ShiftUser;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ApiAttendanceController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Store data attendance to DB
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiSaveAttendance(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'key' => 'required|string',
                'lat' => 'required',
                'longt' => 'required',
                'area_id' => 'required|string|exists:areas,id',
                'q' => 'required|string|in:in,out',
                'worker_id' => 'required|string|exists:users,id',
            ]);

            // Get data setting
            $getSetting = Setting::find(1);
            if (!$getSetting) {
                return response()->json(['message' => 'Settings not found'], 200);
            }

            // Get data from request
            $key = $validated['key'];
            $lat = $validated['lat'];
            $longt = $validated['longt'];
            $areaId = $validated['area_id'];
            $q = $validated['q'];
            $workerId = $validated['worker_id'];

            if ($key !== $getSetting->key_app) {
                return response()->json(['message' => 'the key is wrong!'], 200);
            }

            // Check the area exists
            $getPoly = Location::where('area_id', $areaId)->get(['lat', 'longt']);
            if ($getPoly->isEmpty()) {
                return response()->json(['message' => 'location not found'], 200);
            }

            // Check if user is inside the area
            if (!$this->isInsidePolygon($lat, $longt, $getPoly)) {
                return response()->json(['message' => 'cannot attend'], 200);
            }

            $timezone = $getSetting->timezone;
            $now = Carbon::now()->timezone($timezone);
            $date = $now->format('Y-m-d');
            $time = $now->format('H:i:s');

            if ($q === 'in') {
                return $this->handleCheckIn($workerId, $areaId, $date, $time, $timezone);
            } elseif ($q === 'out') {
                return $this->handleCheckOut($workerId, $areaId, $date, $time, $timezone);
            }

            return response()->json(['message' => 'Error! Wrong Command!'], 200);
        } catch (\Exception $e) {
            Log::error('Error in apiSaveAttendance: ' . $e->getMessage());
            return response()->json(['message' => 'Internal Server Error'], 200);
        }
    }

    /**
     * Return data json for the app
     * @param mixed $date
     * @param mixed $time
     * @param mixed $location
     * @param mixed $query
     * @return array
     */

    public function returnDataJson($date, $time, $location, $query)
    {
        $data = [
            'message' => 'Success!',
            'date' => Carbon::parse($date)->format('Y-m-d'),
            'time' => Carbon::parse($time)->format('H:i:s'),
            'location' => $location,
            'query' => $query,
        ];
        return $data;
    }

    /**
     * Check if user inside the area
     * 
     * @param mixed $x
     * @param mixed $y
     * @param mixed $polygon
     * @return bool
     */
    public function isInsidePolygon($x, $y, $polygon)
    {
        $inside = false;
        for ($i = 0, $j = count($polygon) - 1, $iMax = count($polygon); $i < $iMax; $j = $i++) {
            $xi = $polygon[$i]['lat'];
            $yi = $polygon[$i]['longt'];
            $xj = $polygon[$j]['lat'];
            $yj = $polygon[$j]['longt'];

            $intersect = (($yi > $y) != ($yj > $y))
                && ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi);
            if ($intersect) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    /**
     * Get late hour
     * @param mixed $inTime
     * @param mixed $workerId
     * @return mixed
     */
    public function getLateHour($inTime, $inDate, $workerId)
    {
        $inTime = new Carbon("{$inDate} {$inTime->format('H:i:s')}");
        $workerShift = $this->getDataWorker($workerId)->shift;
        $endTime = Carbon::createFromFormat('H:i:s', $workerShift->end_time)->format('H:i:s');

        $shiftStartTime = $this->isDayPlusOne($workerId, $inTime) && $this->isDayPlusOne($workerId, $endTime)
            ? new Carbon("{$inDate->yesterday()->format('Y-m-d')} {$workerShift->start_time}")
            : new Carbon("{$inDate} {$workerShift->start_time}");

        $lateTime = $inTime->gt($shiftStartTime) ? $inTime->diff($shiftStartTime)->format('%H:%I:%S') : "00:00:00";

        return $lateTime;
    }

    /**
     * Get data shift for worker
     * @param mixed $workerId
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getDataWorker($workerId)
    {
        return ShiftUser::with(['shift', 'user'])
            ->whereHas('user', function ($query) use ($workerId) {
                $query->where('id', $workerId);
            })
            ->first();
    }

    /**
     * Calculate sum start time and late mark time
     * @param mixed $workerId
     * @return string
     */
    public function sumStartLateTime($workerId)
    {
        $getData = $this->getDataWorker($workerId);
        $getStartTime = $getData->shift->start_time;
        $getLateTime = $getData->shift->late_mark_after;
        $result = date("H:i:s", strtotime($getStartTime) + strtotime($getLateTime));

        return $result;
    }

    /**
     * Check if the shift include next day
     * @param mixed $workerId
     * @return bool
     */
    public function isDayPlusOne($workerId, $endtime)
    {
        $getData = $this->getDataWorker($workerId);
        $time = Carbon::createFromFormat('H:i:s', "23:59:59")->format('H:i:s');
        $begintime = Carbon::createFromFormat('H:i:s', $getData->shift->start_time)->format('H:i:s');

        if ($begintime < $endtime) {
            return $begintime <= $time && $time <= $endtime;
        } else {
            return $time >= $begintime || $time <= $endtime;
        }
    }

    /**
     * Get total Working Hours
     * 
     * @param mixed $inTime
     * @param mixed $inDate
     * @param mixed $outTime
     * @return string
     */
    public function getWorkingHours($inTime, $inDate, $outTime)
    {
        $dataOutTime = new Carbon($outTime);
        $dataInTime = new Carbon($inDate->format('Y-m-d') . " " . $inTime);
        return $dataOutTime->diffInHours($dataInTime) . ':' . $dataOutTime->diff($dataInTime)->format('%I:%S');
    }

    /**
     * Get total over hour
     * @param mixed $workerId
     * @param mixed $inDate
     * @param mixed $inTime
     * @param mixed $outDate
     * @param mixed $outTime
     * @return mixed
     */
    public function getOverHours($workerId, $inDate, $inTime, $outTime)
    {
        $inDateTime = new Carbon($inDate->format('Y-m-d') . " " . $inTime);
        $shiftEndTime = $this->getDataWorker($workerId)->shift->end_time;
        $endTime = Carbon::createFromFormat('H:i:s', $shiftEndTime)->format('H:i:s');

        $shiftEndDateTime = $this->isDayPlusOne($workerId, $endTime)
            ? new Carbon($inDate->addDay()->format('Y-m-d') . " " . $shiftEndTime)
            : new Carbon($inDate->format('Y-m-d') . " " . $shiftEndTime);

        if ($inDateTime->gt($shiftEndDateTime) || !$outTime->gt($shiftEndDateTime)) {
            return "00:00:00";
        }

        return $outTime->diff($shiftEndDateTime)->format('%H:%I:%S');
    }

    /**
     * Get total early out hour
     * 
     * @param mixed $workerId
     * @param mixed $inDate
     * @param mixed $inTime
     * @param mixed $outTime
     * @return string
     */
    public function getEarlyOutHour($workerId, $inDate, $inTime, $outTime)
    {
        $inDateTime = new Carbon($inDate->format('Y-m-d') . " " . $inTime);
        $shiftEndTime = $this->getDataWorker($workerId)->shift->end_time;
        $shiftEndDateTime = $this->isDayPlusOne($workerId, $shiftEndTime)
            ? new Carbon($inDate->addDay()->format('Y-m-d') . " " . $shiftEndTime)
            : new Carbon($inDate->format('Y-m-d') . " " . $shiftEndTime);

        if ($inDateTime->gt($shiftEndDateTime) || $outTime->gt($shiftEndDateTime)) {
            return "00:00:00";
        }

        return $shiftEndDateTime->diff($outTime)->format('%H:%I:%S');
    }

    /**
     * Handle the check-in process for a worker.
     *
     * @param mixed $workerId The ID of the worker.
     * @param mixed $areaId The ID of the area where the worker is checking in.
     * @param mixed $date The date of the check-in.
     * @param mixed $time The time of the check-in.
     * @return \Illuminate\Http\JsonResponse The response indicating the result of the check-in process.
     */
    private function handleCheckIn($workerId, $areaId, $date, $time)
    {
        try {
            if ($this->isAlreadyCheckedIn($workerId, $date)) {
                return response()->json(['message' => 'already check-in'], 200);
            }

            $inTime = new Carbon($time);
            $lateTime = $this->getLateHour($inTime, $date, $workerId);
            $location = Area::find($areaId)->name;

            $attendance = $this->createAttendanceRecord($workerId, $date, $areaId, $inTime, $lateTime);

            if ($attendance->save()) {
                return response()->json($this->returnDataJson($date, $time, $location, 'Check-in'));
            }

            return response()->json(['message' => 'Error! Something Went Wrong!'], 200);
        } catch (\Exception $e) {
            Log::error('Error in handleCheckIn: ' . $e->getMessage());
            return response()->json(['message' => 'Internal Server Error'], 200);
        }
    }

    /**
     * Check if the worker has already checked in for the given date.
     *
     * @param mixed $workerId The ID of the worker.
     * @param mixed $date The date to check for an existing check-in.
     * @return bool True if the worker has already checked in, false otherwise.
     */
    private function isAlreadyCheckedIn($workerId, $date)
    {
        return Attendance::where('worker_id', $workerId)
            ->where('date', $date)
            ->whereNotNull('in_time')
            ->whereNotNull('late_time')
            ->whereNull('out_time')
            ->whereNull('out_location_id')
            ->exists();
    }

    /**
     * Create a new attendance record for the worker.
     *
     * @param mixed $workerId The ID of the worker.
     * @param mixed $date The date of the attendance.
     * @param mixed $areaId The ID of the area where the worker is checking in.
     * @param \Carbon\Carbon $inTime The check-in time.
     * @param \Carbon\Carbon $lateTime The late time.
     * @return \App\Models\Attendance The created attendance record.
     */
    private function createAttendanceRecord($workerId, $date, $areaId, $inTime, $lateTime)
    {
        $attendance = new Attendance();
        $attendance->worker_id = $workerId;
        $attendance->date = $date;
        $attendance->in_location_id = $areaId;
        $attendance->in_time = $inTime;
        $attendance->late_time = $lateTime;

        return $attendance;
    }

    /**
     * Handle the check-out process for a worker.
     *
     * @param mixed $workerId The ID of the worker.
     * @param mixed $areaId The ID of the area where the worker is checking out.
     * @param mixed $date The date of the check-out.
     * @param \Carbon\Carbon $time The check-out time.
     * @return \Illuminate\Http\JsonResponse The response indicating the result of the check-out process.
     */
    private function handleCheckOut($workerId, $areaId, $date, $time)
    {
        try {
            $attendance = Attendance::where('worker_id', $workerId)
                ->whereNull('out_time')
                ->whereNull('date_out')
                ->whereNull('out_location_id')
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$attendance) {
                return response()->json(['message' => 'check-in first'], 200);
            }

            $outTime = new Carbon($time);
            $attendance->out_time = $outTime;
            $attendance->over_time = $this->getOverHours($workerId, $attendance->date, $attendance->in_time, $outTime);
            $attendance->work_hour = $this->getWorkingHours($attendance->in_time, $attendance->date, $outTime);
            $attendance->early_out_time = $this->getEarlyOutHour($workerId, $attendance->date, $attendance->in_time, $outTime);
            $attendance->date_out = $date;
            $attendance->out_location_id = $areaId;

            if ($attendance->save()) {
                $location = Area::find($areaId)->name;
                return response()->json($this->returnDataJson($date, $time, $location, 'Check-Out'));
            }

            return response()->json(['message' => 'Error! Something Went Wrong!'], 200);
        } catch (\Exception $e) {
            Log::error('Error in handleCheckOut: ' . $e->getMessage());
            return response()->json(['message' => 'Internal Server Error'], 200);
        }
    }
}
