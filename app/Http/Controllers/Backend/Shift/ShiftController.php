<?php
namespace App\Http\Controllers\Backend\Shift;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Utils\Tools\ToolsController;
use App\Http\Controllers\Utils\Activity\SaveActivityLogController;
use Yajra\Datatables\Datatables;
use App\Models\Shift;
use Auth;
use Config;

class ShiftController extends Controller
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
     * More info DataTables : https://yajrabox.com/docs/laravel-datatables/master
     * 
     * @param \Yajra\Datatables\Datatables $datatables
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    public function index(Datatables $datatables)
    {
        $columns = [
            'id' => ['title' => 'ID'],
            'name',
 	 	 	'start_time',
 	 	 	'end_time',
 	 	 	'late_mark_after' => ['title' => 'Late Mark After (In Minutes)'],
            'use_shift' => ['title' => 'Number Of Users Use Shift'],
            'action' => ['orderable' => false, 'searchable' => false]
        ];

        if ($datatables->getRequest()->ajax()) {
            return $datatables->of(Shift::with('users')->get())
            ->addColumn('name', function (Shift $data) {
                $color = $data->color == null ? 'snow': $data->color;
                return '<span style="color: '. $color .'" class="badge badge-secondary">' . $data->name . '</span>';
            })
            ->addColumn('start_time', function (Shift $data) {
                return $data->start_time;
            })
            ->addColumn('end_time', function (Shift $data) {
                return $data->end_time;
            })
            ->addColumn('late_mark_after', function (Shift $data) {
                return $data->late_mark_after;
            })
            ->addColumn('use_shift', function (Shift $data) {
                $countData = $data->users;
                return count($countData) <= 1 ? count($countData) . " User" : count($countData) . " Users";
            })
            ->addColumn('action', function (Shift $data) {
                $routeEdit = route($this->getRoute() . ".edit", $data->id );
                $routeDelete = route($this->getRoute() . ".delete", $data->id);

                $button = '<div class="col-sm-12"><div class="row">';
                if (Auth::user()->hasRole('administrator')) { // Check the role
                    $button .= '<div class="col-sm-6"><a href="'.$routeEdit.'"><button class="btn btn-primary"><i class="fa fa-edit"></i></button></a></div> ';
                    $button .= '<div class="col-sm-6"><a href="'.$routeDelete.'" class="delete-button"><button class="btn btn-danger"><i class="fa fa-trash"></i></button></a></div>';
                } else {
                    $button = '<a href="#"><button class="btn btn-primary disabled"><i class="fa fa-edit"></i></button></a> ';
                    $button .= '<a href="#"><button class="btn btn-danger disabled"><i class="fa fa-trash"></i></button></a>';
                }
                $button .= '</div></div>';
                return $button;
            })
            ->rawColumns(['action', 'use_shift', 'name'])
            ->toJson();
        }

        $toolsController = new ToolsController();
        $columnsArrExPr = $toolsController->ExportColumnArr(0, 6);

        $html = $datatables->getHtmlBuilder()
            ->columns($columns)
            ->parameters([
                'order' => [[1,'asc']],
                'responsive' => true,
                'autoWidth' => false,
                'lengthMenu' => [
                    [ 10, 25, 50, -1 ],
                    [ '10 rows', '25 rows', '50 rows', 'Show all' ]
                ],
                'dom' => 'Bfrtip',
                'buttons' => $toolsController->buttonDatatables($columnsArrExPr),
            ]);

        return view('backend.shifts.index', compact('html'));
    }

    /**
     * Show the form for creating a new resource.
     * 
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function add()
    {

        $data = new Shift();
        $data->form_action = $this->getRoute() . '.create';
        // Add page type here to indicate that the form.blade.php is in 'add' mode
        $data->page_type = 'add';
        $data->button_text = 'Add';

        return view('backend.shifts.form', [
            'data' => $data,
        ]);
    }

    /**
     * Get named route depends on which user is logged in
     * 
     * @return string
     */
    private function getRoute()
    {
        return 'shifts';
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function create(Request $request)
    {
        $new = $request->all();
        $this->validator($new, 'create')->validate();
        try {
            $createNew = Shift::create($new);
            if ($createNew) {

                $createNew->save();

                // Save log
                $controller = new SaveActivityLogController();
                $controller->saveLog($new, "Create new shift");

                // Create is successful, back to list
                return redirect()->route($this->getRoute())->with('success', Config::get('const.SUCCESS_CREATE_MESSAGE'));
            }

            // Create is failed
            return redirect()->route($this->getRoute())->with('error', Config::get('const.FAILED_CREATE_MESSAGE'));
        } catch (\Exception $e) {
            // Create is failed
            return redirect()->route($this->getRoute())->with('error', Config::get('const.FAILED_CREATE_MESSAGE'));
        }
    }

    /**
     * Validator data.
     *
     * @param array $data
     * @param $type
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data, $type)
    {
        // Determine if password validation is required depending on the calling
        return Validator::make($data, [
            // Validator
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     * 
     * @param mixed $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit($id)
    {
        $data = Shift::find($id);
        $data->form_action = $this->getRoute() . '.update';
        // Add page type here to indicate that the form.blade.php is in 'update' mode
        $data->page_type = 'update';
        $data->button_text = 'Update';

        return view('backend.shifts.form', [
            'data' => $data,
        ]);
    }

    /**
     * Update the specified resource in storage.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $new = $request->all();
        try {
            $currentData = Shift::find($request->get('id'));
            if ($currentData) {
                $this->validator($new, 'update')->validate();

                // Update
                $currentData->update($new);

                // Save log
                $controller = new SaveActivityLogController();
                $controller->saveLog($new, "Update shift");

                return redirect()->route($this->getRoute())->with('success', Config::get('const.SUCCESS_UPDATE_MESSAGE'));
            }

            // If update is failed
            return redirect()->route($this->getRoute())->with('error', Config::get('const.FAILED_UPDATE_MESSAGE'));
        } catch (\Exception $e) {
            // If update is failed
            return redirect()->route($this->getRoute())->with('error', Config::get('const.FAILED_CREATE_MESSAGE'));
        }
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @param mixed $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete($id)
    {
        try {
            // Delete
            $data = Shift::find($id);
            $data->delete();

            // Save log
            $controller = new SaveActivityLogController();
            $controller->saveLog($data->toArray(), "Delete shift");

            //delete success
            return redirect()->route($this->getRoute())->with('success', Config::get('const.SUCCESS_DELETE_MESSAGE'));
        } catch (\Exception $e) {
            // delete failed
            return redirect()->route($this->getRoute())->with('error', Config::get('const.ERROR_FOREIGN_KEY'));
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->route($this->getRoute())->with('error', Config::get('const.ERROR_FOREIGN_KEY'));
        }
    }
}
