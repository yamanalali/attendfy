<?php

namespace App\Http\Controllers\Backend\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Utils\Tools\ToolsController;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function store(Request $request)
    {
        // Basic validation
        $request->validate([
            'worker_id' => 'required|integer',
            'date'      => 'required|date',
        ]);

        // 1. Check if user has already checked in today
        $existing = Attendance::where('worker_id', $request->worker_id)
            ->where('date', $request->date)
            ->first();

        if ($existing) {
            // If you want to block further check-ins for the day:
            return response()->json([
                'status'  => 'error',
                'message' => 'You have already checked in today.'
            ], 409); // 409 Conflict
        }

        // 2. Otherwise, proceed to create a new attendance record
        try {
            $attendance = new Attendance();
            $attendance->worker_id       = $request->worker_id;
            $attendance->date           = $request->date;
            $attendance->date_out       = $request->date_out;
            $attendance->in_time        = $request->in_time;
            $attendance->out_time       = $request->out_time;
            $attendance->work_hour      = $request->work_hour;
            $attendance->over_time      = $request->over_time;
            $attendance->late_time      = $request->late_time;
            $attendance->early_out_time = $request->early_out_time;
            $attendance->in_location_id = $request->in_location_id;
            $attendance->out_location_id= $request->out_location_id;
            $attendance->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Check-in complete.',
                'data' => $attendance
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to log attendance.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function checkOut(Request $request)
    {
        $request->validate([
            'worker_id' => 'required|integer',
            'date'      => 'required|date',
            'out_time'  => 'required' // 'HH:MM:SS'
        ]);

        // Find today's attendance record for that user
        $attendance = Attendance::where('worker_id', $request->worker_id)
            ->where('date', $request->date)
            ->first();

        if (!$attendance) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No attendance record found to check out.'
            ], 404);
        }

        // Update out_time
        $attendance->out_time = $request->out_time;
        $attendance->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Check-out complete.',
            'data'    => $attendance
        ], 200);
    }

    /**
     * Show the application dashboard.
     * More info DataTables : https://yajrabox.com/docs/laravel-datatables/master
     *
     * @param \Yajra\Datatables\Datatables $datatables
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */


    public function index(Datatables $datatables, Request $request)
    {
        $columns = [
            'id' => ['title' => 'No.', 'orderable' => false, 'searchable' => false, 'render' => function () {
                return 'function(data,type,fullData,meta){return meta.settings._iDisplayStart+meta.row+1;}';
            }],
            'name' => ['name' => 'user.name', 'title' => 'Employee Name'],
            'date' => ['title' => 'In Date'],
            'in_time',
            'date_out' => ['title' => 'Out Date'],
            'out_time',
            'work_hour',
            'over_time',
            'late_time',
            'early_out_time',
            'in_location_id' => ['name' => 'areaIn.name', 'title' => 'In Location'],
            'out_location_id' => ['name' => 'areaOut.name', 'title' => 'Out Location']
        ];

        // Validate date input
        $from = isset($request->dateFrom) ? Carbon::parse($request->dateFrom)->format('Y-m-d') : null;
        $to = isset($request->dateTo) ? Carbon::parse($request->dateTo)->format('Y-m-d') : null;

        if ($datatables->getRequest()->ajax()) {
            $query = Attendance::with(['user', 'user.shifts', 'areaIn', 'areaOut'])
                ->select('attendances.*');

            if ($from && $to) {
                $query->whereBetween('date', [$from, $to]);
            }

            // Apply role-based filtering
            if (Auth::user()->hasRole('staff') || Auth::user()->hasRole('admin')) {
                $query->where('worker_id', Auth::user()->id);
            }

            return $datatables->of($query)
                ->addColumn('name', function (Attendance $data) {
                    // Ensure shifts exist before accessing
                    $color = optional(optional($data->user)->shifts->first())->color ?? '#000000';
                    return '<span style="color: '. $color .'" class="badge badge-secondary">' . e($data->user->name) . '</span>';
                })
                ->addColumn('late_time', function (Attendance $data) {
                    return $data->late_time > '00:00:00'
                        ? '<span style="color: red"><b>' . e($data->late_time) . '</b></span>'
                        : e($data->late_time);
                })
                ->addColumn('over_time', function (Attendance $data) {
                    return $data->over_time > '00:00:00'
                        ? '<span style="color: green"><b>' . e($data->over_time) . '</b></span>'
                        : e($data->over_time);
                })
                ->addColumn('early_out_time', function (Attendance $data) {
                    return $data->early_out_time > '00:00:00'
                        ? '<span style="color: red"><b>' . e($data->early_out_time) . '</b></span>'
                        : e($data->early_out_time);
                })
                ->addColumn('in_location_id', function (Attendance $data) {
                    return $data->in_location_id ? e(optional($data->areaIn)->name) : '';
                })
                ->addColumn('out_location_id', function (Attendance $data) {
                    return $data->out_location_id ? e(optional($data->areaOut)->name) : '';
                })
                ->rawColumns(['name', 'late_time', 'over_time', 'early_out_time', 'in_location_id', 'out_location_id'])
                ->toJson();
        }

        $toolsController = new ToolsController();
        $columnsArrExPr = $toolsController->ExportColumnArr(0, 12);

        $html = $datatables->getHtmlBuilder()
            ->columns($columns)
            ->minifiedAjax('', $this->scriptMinifiedJs())
            ->parameters([
                'order' => [[2, 'desc'], [3, 'desc']],
                'responsive' => true,
                'autoWidth' => false,
                'lengthMenu' => [
                    [10, 25, 50, -1],
                    ['10 rows', '25 rows', '50 rows', 'Show all']
                ],
                'dom' => 'Bfrtip',
                'buttons' => $toolsController->buttonDatatables($columnsArrExPr),
            ]);

        return view('backend.attendances.index', compact('html'));
    }


    /**
     * Get script for the date range.
     *
     * @return string
     */
    public function scriptMinifiedJs()
    {
        // Script to minified the ajax
        return <<<CDATA
            var formData = $("#date_filter").find("input").serializeArray();
            $.each(formData, function(i, obj){
                data[obj.name] = obj.value;
            });
CDATA;
    }
}
