<?php

namespace App\Http\Controllers\Backend\Analytic;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Utils\Tools\ToolsController;
use Illuminate\Http\Request;
use App\Models\Attendance;
use Yajra\Datatables\Datatables;
use DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AnalyticsController extends Controller
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
     * Show the data as chart and datatables.
     * More info Library : https://github.com/fxcosta/laravel-chartjs
     * More info ChartJs : https://www.chartjs.org/
     * More info DataTables : https://yajrabox.com/docs/laravel-datatables/master
     * 
     * @param \Yajra\Datatables\Datatables $datatables
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    public function index(Datatables $datatables, Request $request)
    {
        $from = isset($request->from) ? Carbon::parse($request->from)->startOfDay() : '';
        $to = isset($request->to) ? Carbon::parse($request->to)->endOfDay() : '';
        $param['from'] = $from != '' ? Carbon::parse($from)->format('Y-m-d') : '';
        $param['to'] = $to != '' ? Carbon::parse($to)->format('Y-m-d') : '';

        $param['type'] =  1;
        // Run query for analytic
        $getDataAnalytics = $this->getDataAnalyticDb($param);

        if ($param['from'] && $param['to']) {
            $dateArrFrom =  Carbon::parse($param['from'])->startOfDay();
            $dateArrTo =  Carbon::parse($param['to'])->endOfDay();
        } else {
            $dateArrFrom =  Carbon::parse(Carbon::now()->firstOfMonth())->startOfDay();
            $dateArrTo =  Carbon::parse(Carbon::now()->lastOfMonth())->endOfDay();
        }

        // Generate date with CarbonPeriod
        $daysOfMonth = collect(
            CarbonPeriod::create(
                $dateArrFrom,
                $dateArrTo
            )
        )
            ->map(function ($getDataAnalytics) {
                return [
                    'label' => $getDataAnalytics->format('F d, Y'),
                    'countLateTime' => 0,
                    'countOverTime' => 0,
                    'countEarlyOutTime' => 0,
                ];
            })
            ->keyBy('label')
            ->merge(
                $getDataAnalytics->keyBy('label')
            )
            ->values();

        $returnData['label'] = [];
        $returnData['dataSum'] = [];

        foreach ($daysOfMonth as $value) {
            $returnData['label'][] = $value['label'];
            $returnData['dataLate'][] = (int)$value['countLateTime'];
            $returnData['dataOver'][] = (int)$value['countOverTime'];
            $returnData['dataEarlyOut'][] = (int)$value['countEarlyOutTime'];
        }

        $analytic = $this->chartAnalytics('analyticHistories', "Analytic", $returnData);

        // DataTables
        $columns = [
            'id' => ['title' => 'No.', 'orderable' => false, 'searchable' => false, 'render' => function () {
                return 'function(data,type,fullData,meta){return meta.settings._iDisplayStart+meta.row+1;}';
            }],
            'name' => ['name' => 'user.name'],
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

        $param['type'] =  2;
        // Run query for dataTables
        $getDataAnalytics = $this->getDataAnalyticDb($param);

        if ($datatables->getRequest()->ajax()) {
            return $datatables->of($getDataAnalytics)
                ->addColumn('name', function (Attendance $data) {
                    return $data->user->name;
                })
                ->addColumn('late_time', function (Attendance $data) {
                    return $data->late_time > '00:00:00' ? '<span style="color: red"><b>'. $data->late_time . '</b></span>' : $data->late_time;
                })
                ->addColumn('over_time', function (Attendance $data) {
                    return $data->over_time > '00:00:00' ? '<span style="color: green"><b>'. $data->over_time . '</b></span>' : $data->over_time;
                })
                ->addColumn('early_out_time', function (Attendance $data) {
                    return $data->early_out_time > '00:00:00' ? '<span style="color: red"><b>'. $data->early_out_time . '</b></span>' : $data->early_out_time;
                })
                ->addColumn('in_location_id', function (Attendance $data) {
                    return $data->in_location_id == null ? '' : $data->areaIn->name;
                })
                ->addColumn('out_location_id', function (Attendance $data) {
                    return $data->out_location_id == null ? '' : $data->areaOut->name;
                })
                ->rawColumns(['early_out_time', 'over_time', 'late_time', 'name', 'out_location_id', 'in_location_id'])
                ->toJson();
        }

        $toolsController = new ToolsController();
        $columnsArrExPr = $toolsController->ExportColumnArr(0, 12);
        
        $html = $datatables->getHtmlBuilder()
            ->columns($columns)
            ->parameters([
                'order' => [[2,'desc'], [3,'desc']],
                'responsive' => true,
                'autoWidth' => false,
                'searching' => false,
                'lengthMenu' => [
                    [ 10, 25, 50, -1 ],
                    [ '10 rows', '25 rows', '50 rows', 'Show all' ]
                ],
                'dom' => 'Bfrtip',
                'buttons' => $toolsController->buttonDatatables($columnsArrExPr),
            ]);

        return view('backend.analytics.index', compact('analytic', 'param', 'html'));
    }

    /**
     * * Function get analytic from DB For analytic and datatables.
     * param 1 = analytic
     * param 2 = datatables
     * 
     * @param mixed $param
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Collection|array<\Illuminate\Database\Eloquent\Builder>
     */
    public function getDataAnalyticDb($param)
    {
        $getDataAnalytics = Attendance::with('user', 'areaIn', 'areaOut')
          ->select('attendances.*');

        if ($param['type'] == 1) {
            $getDataAnalytics = $getDataAnalytics->select(
                DB::raw("DATE_FORMAT(date, '%M %d, %Y') as label"),
                DB::raw("count(CASE WHEN late_time  > '00:00:00' THEN 1 ELSE null end) as countLateTime"),
                DB::raw("count(CASE WHEN over_time  > '00:00:00' THEN 1 ELSE null end) as countOverTime"),
                DB::raw("count(CASE WHEN early_out_time  > '00:00:00' THEN 1 ELSE null end) as countEarlyOutTime")
            );
        }

        if ($param['from'] && $param['to']) {
            $dateArrFrom =  Carbon::parse($param['from'])->startOfDay();
            $dateArrTo =  Carbon::parse($param['to'])->endOfDay();
            $getDataAnalytics = $getDataAnalytics->whereBetween('date', [$param['from'], $param['to']]);
        } else {
            $dateArrFrom =  Carbon::parse(Carbon::now()->firstOfMonth())->startOfDay();
            $dateArrTo =  Carbon::parse(Carbon::now()->lastOfMonth())->endOfDay();
            $getDataAnalytics = $getDataAnalytics->whereBetween('date', [$dateArrFrom, $dateArrTo]);
        }

        if ($param['type'] == 1) {
            $getDataAnalytics = $getDataAnalytics->groupBy('date');
            $getDataAnalytics = $getDataAnalytics->get();
        } else {
            $getDataAnalytics = $getDataAnalytics->where(function ($query) {
                $query->whereTime('over_time', '>', '00:00:00')
                    ->orWhereTime('early_out_time', '>', '00:00:00')
                    ->orWhereTime('late_time', '>', '00:00:00');
            });
        }

        return $getDataAnalytics;
    }

    /**
     * Function show chart.
     * 
     * @param mixed $name
     * @param mixed $title
     * @param mixed $data
     * @return mixed
     */
    public function chartAnalytics($name, $title, $data)
    {
        $chartjs = app()->chartjs
            ->name($name)
            ->type('line')
            ->size(['width' => 800, 'height' => 500])
            ->labels($data['label'])
            ->datasets([
                [
                    "label" => "Come Late",
                    'borderDash' => [5, 5],
                    'pointRadius' => true,
                    'backgroundColor' => "rgba(255, 34, 21, 0.31)",
                    'borderColor' => "rgba(255, 34, 21, 0.7)",
                    "pointColor" => "rgba(255, 34, 21, 0.7)",
                    "pointStrokeColor" => "rgba(255, 34, 21, 0.7)",
                    "pointHoverBackgroundColor" => "#fff",
                    "pointHighlightStroke" => "rgba(220,220,220,1)",
                    'data' => $data['dataLate']
                ],
                [
                    "label" => "Overtime Work",
                    'backgroundColor' => 'rgba(210, 214, 222, 1)',
                    'borderColor' => 'rgba(210, 214, 222, 1)',
                    'pointRadius' => true,
                    "pointColor" => 'rgba(210, 214, 222, 1)',
                    "pointStrokeColor" => '#c1c7d1',
                    "pointHighlightFill" => "#fff",
                    "pointHighlightStroke" => 'rgba(220,220,220,1)',
                    'data' => $data['dataOver']
                ],
                [
                    "label" => "Early Out Time",
                    'backgroundColor' => 'rgba(60,141,188,0.9)',
                    'borderColor' => 'rgba(60,141,188,0.8)',
                    'pointRadius' => true,
                    "pointColor" => '#3b8bba',
                    "pointStrokeColor" => 'rgba(60,141,188,1)',
                    "pointHighlightFill" => "#fff",
                    "pointHighlightStroke" => 'rgba(60,141,188,1)',
                    'data' => $data['dataEarlyOut']
                ],
            ])
            ->options([]);

        $chartjs->optionsRaw([
            'title' => [
                'text' => $title,
                'display' => true,
                'position' => "top",
                'fontSize' => 18,
                'fontColor' => "#000"
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
            'legend' => [
                'position' => 'top',
            ],
            'scales' => [
                'xAxes' => [
                    [
                        'gridLines' => [
                            'display' => false
                        ]
                    ]
                ],
                'yAxes' => [
                    [
                        'gridLines' => [
                            'display' => false
                        ]
                    ]
                ],
            ]
        ]);

        return $chartjs;
    }
}
