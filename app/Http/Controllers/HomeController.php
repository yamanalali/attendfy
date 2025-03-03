<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;

class HomeController extends Controller
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
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        // Get start time to check late worker
        $getSetting = Setting::find(1);

        // Get all data for summary
        $userCount = User::count();
        $attendaceToday = Attendance::where('date',  Carbon::now()->timezone($getSetting->timezone)->format('Y-m-d'))->count();
        $attendanceLateToday = Attendance::where('date',  Carbon::now()->timezone($getSetting->timezone)->format('Y-m-d'))
            ->where('late_time', '>', '00:00:00')
            ->count();
        $areaCount = Area::count();
        $events = Event::whereBetween(
            'start_date',
            [
                Carbon::now()->startOfMonth()->format('Y-m-d'),
                Carbon::now()->endOfMonth()->format('Y-m-d')
            ]
        )->get()->sortBy('start_date');

        return view('home', compact('userCount', 'attendaceToday', 'attendanceLateToday', 'areaCount', 'events'));
    }
}
