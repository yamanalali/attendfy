<?php

namespace App\Http\Controllers\Backend\Users;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Utils\Tools\ToolsController;
use App\Models\ShiftUser;
use Illuminate\Http\Request;
use App\Http\Controllers\Utils\Activity\SaveActivityLogController;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Yajra\Datatables\Datatables;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use Auth;
use Config;
use File;

class UsersController extends Controller
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
     * @return mixed
     */
    public function index(Datatables $datatables)
    {
        $columns = [
            'id' => ['title' => 'No.', 'orderable' => false, 'searchable' => false, 'render' => function () {
                return 'function(data,type,fullData,meta){return meta.settings._iDisplayStart+meta.row+1;}';
            }],
            'image',
            'name',
            'email',
            'role',
            'shift' => ['name' => 'shifts.name'],
            'action' => ['orderable' => false, 'searchable' => false]
        ];

        if ($datatables->getRequest()->ajax()) {
            $data = User::with('shifts');

            return $datatables->of($data)
                ->addColumn('image', function (User $data) {
                    $getAssetFolder = asset('uploads/' . $data->image);
                    return '<img src="' . $getAssetFolder . '" width="30px" class="img-circle elevation-2">';
                })
                ->addColumn('shift', function (User $data) {
                    return count($data->shifts) > 0 ? '<span style="color: ' . $data->shifts[0]->color . '" class="badge badge-secondary">' . $data->shifts[0]->name . '</span>' : "No Data";
                })
                ->addColumn('action', function (User $data) {
                    $routeEdit = route($this->getRoute() . '.edit', $data->id);
                    $routeDelete = route($this->getRoute() . '.delete', $data->id);

                    // Check is administrator
                    if (Auth::user()->hasRole('administrator')) {
                        $button = '<a href="' . $routeEdit . '"><button class="btn btn-primary"><i class="fa fa-edit"></i></button></a> ';
                        $button .= '<a href="' . $routeDelete . '" class="delete-button"><button class="btn btn-danger"><i class="fa fa-trash"></i></button></a>';
                    } else {
                        $button = '<a href="#"><button class="btn btn-primary disabled"><i class="fa fa-edit"></i></button></a> ';
                        $button .= '<a href="#"><button class="btn btn-danger disabled"><i class="fa fa-trash"></i></button></a>';
                    }
                    return $button;
                })
                ->addColumn('role', function (User $user) {
                    return Role::where('id', $user->role)->first()->display_name;
                })
                ->rawColumns(['action', 'image', 'shift'])
                ->toJson();
        }

        $toolsController = new ToolsController();
        $columnsArrExPr = $toolsController->ExportColumnArr(0, 6);

        $html = $datatables->getHtmlBuilder()
            ->columns($columns)
            ->parameters([
                'order' => [[0, 'asc']],
                'responsive' => true,
                'autoWidth' => false,
                'lengthMenu' => [
                    [10, 25, 50, -1],
                    ['10 rows', '25 rows', '50 rows', 'Show all']
                ],
                'dom' => 'Bfrtip',
                'buttons' => $toolsController->buttonDatatables($columnsArrExPr),
            ]);

        return view('backend.users.index', compact('html'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function add()
    {
        $data = new User();
        $data->form_action = $this->getRoute() . '.create';
        // Add page type here to indicate that the form.blade.php is in 'add' mode
        $data->page_type = 'add';
        $data->button_text = 'Add';

        if (Auth::user()->hasRole('administrator')) {
            return view('backend.users.form', [
                'data' => $data,
                'role' => Role::orderBy('id')->pluck('display_name', 'id'),
                'shift' => Shift::orderBy('id')->pluck('name', 'id'),
            ]);
        }

        return view('backend.users.form', [
            'data' => $data,
            'role' => Role::whereNotIn('id', [1, 2])->orderBy('id')->pluck('display_name', 'id'),
        ]);
    }

    /**
     * Get named route depends on which user is logged in
     *
     * @return string
     */
    private function getRoute()
    {
        return 'users';
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
            $new['password'] = bcrypt($new['password']);
            $createNew = User::create($new);
            if ($createNew) {
                // Attach role
                $createNew->roles()->attach($new['role']);

                // upload image
                if ($request->hasFile('image')) {
                    $file = $request->file('image');
                    // image file name example: [news_id]_image.jpg
                    ${'image'} = $createNew->id . "_image." . $file->getClientOriginalExtension();
                    // save image to the path
                    $file->move(Config::get('const.UPLOAD_PATH'), ${'image'});
                    $createNew->{'image'} = ${'image'};
                } else {
                    $createNew->{'image'} = 'default-user.png';
                }

                // Save user
                $createNew->save();

                $saveShift = new ShiftUser();
                $saveShift->worker_id = $createNew->id;
                $saveShift->shift_id = $new['shift_id'];
                $saveShift->save();

                // Save log
                $controller = new SaveActivityLogController();
                $controller->saveLog($new, "Create new user");

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
     * @param mixed $data
     * @param mixed $type
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data, $type)
    {
        // Determine if password validation is required depending on the calling
        return Validator::make($data, [
            // Add unique validation to prevent for duplicate email while forcing unique rule to ignore a given ID
            'email' => $type == 'create' ? 'email|required|string|max:255|unique:users' : 'required|string|max:255|unique:users,email,' . $data['id'],
            // (update: not required, create: required)
            'password' => $type == 'create' ? 'required|string|min:6|max:255' : '',
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
        $data = User::find($id);
        $data->form_action = $this->getRoute() . '.update';
        // Add page type here to indicate that the form.blade.php is in 'update' mode
        $data->page_type = 'update';
        $data->button_text = 'Update';

        if ($data->role != 1) $data->shift_id = ShiftUser::where('worker_id', $data->id)->first()->shift_id;

        if (Auth::user()->hasRole('administrator')) {
            return view('backend.users.form', [
                'data' => $data,
                'role' => Role::orderBy('id')->pluck('display_name', 'id'),
                'shift' => Shift::orderBy('id')->pluck('name', 'id'),
            ]);
        }

        return view('backend.users.form', [
            'data' => $data,
            'role' => Role::whereNotIn('id', [1, 2])->orderBy('id')->pluck('display_name', 'id'),
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
            $currentData = User::find($request->get('id'));
            if ($currentData) {
                $this->validator($new, 'update')->validate();

                if (!$new['password']) {
                    $new['password'] = $currentData['password'];
                } else {
                    $new['password'] = bcrypt($new['password']);
                }

                if ($currentData->role != $new['role']) {
                    $currentData->roles()->sync($new['role']);
                }

                // Handle image deletion and upload in a single block
                if ($request->get('image_delete') != null) {
                    $new['image'] = 'default-user.png'; // Set to default only if deleted

                    if ($currentData->{'image'} != 'default-user.png') {
                        $filePath = Config::get('const.UPLOAD_PATH') . $currentData['image'];
                        if (File::exists($filePath)) {
                            File::delete($filePath);
                        }
                    }
                }

                // If new image is being uploaded
                if ($request->hasFile('image')) {
                    $file = $request->file('image');
                    ${'image'} = $currentData->id . "_image." . $file->getClientOriginalExtension();
                    $new['image'] = ${'image'};
                    $pathPhoto = Config::get('const.UPLOAD_PATH');
                    $file->move($pathPhoto, ${'image'});

                    // Resize photo when upload
                    $toolsController = new ToolsController();
                    $toolsController->resizePhoto($pathPhoto . $new['image'], 196);
                }

                if (!$request->hasFile('image') && $request->get('image_delete') == null) {
                    $new['image'] = $currentData['image']; // Retain old image if no new image and not deleted
                }

                // Update
                $currentData->update($new);

                // Admistrator cannot have shift data
                // 1 => Adminstrator role
                if ($currentData->role != 1) {
                    $saveShift        = ShiftUser::where('worker_id', $currentData->id)->first();
                    $new['worker_id'] = $currentData->id;
                    $saveShift->update($new);
                }

                // Save log
                $controller = new SaveActivityLogController();
                $controller->saveLog($new, "Update user");

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
            if (Auth::user()->id != $id) {

                $shiftUser = ShiftUser::where('worker_id', $id)->first();
                if ($shiftUser) {
                    $shiftUser->delete();
                }
                // delete
                $user = User::find($id);
                $user->roles()->detach();

                // Delete the image
                if ($user->{'image'} != 'default-user.png') {
                    @unlink(Config::get('const.UPLOAD_PATH') . $user['image']);
                }

                // Delete the data DB
                $user->delete();

                // Save log
                $controller = new SaveActivityLogController();
                $controller->saveLog($user->toArray(), "Delete user");

                //delete success
                return redirect()->route($this->getRoute())->with('success', Config::get('const.SUCCESS_DELETE_MESSAGE'));
            }
            // delete failed
            return redirect()->route($this->getRoute())->with('error', Config::get('const.FAILED_DELETE_SELF_MESSAGE'));
        } catch (\Exception $e) {
            // delete failed
            return redirect()->route($this->getRoute())->with('error', $e->getMessage());
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->route($this->getRoute())->with('error', Config::get('const.ERROR_FOREIGN_KEY'));
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function import()
    {
        $data = new User();
        $data->form_action = $this->getRoute() . '.importData';
        // Add page type here to indicate that the form.blade.php is in 'add' mode
        $data->page_type = 'add';
        $data->button_text = 'Import';

        return view('backend.users.import', [
            'data' => $data,
        ]);
    }

    /**
     * Upload and import data from csv file.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function importData(Request $request)
    {
        $errorMessage = '';
        $errorArr = array();

        // If file extension is 'csv'
        if ($request->hasFile('import')) {
            $file = $request->file('import');

            // File Details
            $extension = $file->getClientOriginalExtension();

            // If file extension is 'csv'
            if ($extension == 'csv') {
                $fp = fopen($file, 'rb');

                $header = fgetcsv($fp, 0, ',');
                $countheader = count($header);

                // Check is csv file is correct format
                if ($countheader < 7 && in_array('email', $header, true) && in_array('first_name', $header, true) && in_array('last_name', $header, true) && in_array('role', $header, true) && in_array('password', $header, true)) {
                    // Loop the row data csv
                    while (($csvData = fgetcsv($fp)) !== false) {
                        $csvData = array_map('utf8_encode', $csvData);

                        // Row column length
                        $dataLen = count($csvData);

                        // Skip row if length != 6
                        if (!($dataLen == 6)) {
                            continue;
                        }

                        // Assign value to variables
                        $email = trim($csvData[0]);
                        $first_name = trim($csvData[1]);
                        $last_name = trim($csvData[2]);
                        $name = $first_name . ' ' . $last_name;
                        $role = trim($csvData[3]);
                        $shiftId = trim($csvData[5]);

                        // Insert data to users table
                        // Check if any duplicate email
                        if ($this->checkDuplicate($email, 'email')) {
                            $errorArr[] = $email;
                            $str = implode(", ", $errorArr);
                            $errorMessage = '-Some data email already exists ( ' . $str . ' )';
                            continue;
                        }

                        $password = trim($csvData[4]);
                        $hashed = bcrypt($password);

                        $data = array(
                            'email' => $email,
                            'name' => $name,
                            'role' => $role,
                            'password' => $hashed,
                            'image' => 'default-user.png',
                        );

                        // create the user
                        $createNew = User::create($data);

                        // Attach role
                        $createNew->roles()->attach($role);

                        // Save user
                        $createNew->save();

                        // Save the shift data
                        $saveShift = new ShiftUser();
                        $saveShift->worker_id = $createNew->id;
                        $saveShift->shift_id = $shiftId;
                        $saveShift->save();
                    }

                    if ($errorMessage == '') {
                        return redirect()->route($this->getRoute())->with('success', 'Imported was success!');
                    }
                    return redirect()->route($this->getRoute())->with('warning', 'Imported was success! <br><b>Note: We do not import this data data because</b><br>' . $errorMessage);
                }
                return redirect()->route($this->getRoute())->with('error', 'Import failed! You are using the wrong CSV format. Please use the CSV template to import your data.');
            }
            return redirect()->route($this->getRoute())->with('error', 'Please choose file with .CSV extension.');
        }

        return redirect()->route($this->getRoute())->with('error', 'Please select CSV file.');
    }

    /**
     * Function check email is exist or not.
     *
     * @param mixed $data
     * @param mixed $typeCheck
     * @return bool
     */
    public function checkDuplicate($data, $typeCheck)
    {
        if ($typeCheck == 'email') {
            $isExists = User::where('email', $data)->first();
        }

        if ($isExists) {
            return true;
        }

        return false;
    }

    public function getUsersWithAvatar()
    {
        // Fetch all users with their image field
        $users = User::select('id', 'name', 'email', 'image')->get();

        // Debugging: Check image values
        foreach ($users as $user) {
            \Log::info("User ID: {$user->id}, Image: {$user->image}");
        }

        // Append the full URL for each user's image
        $users->transform(function ($user) {
            if (!empty($user->image) && file_exists(public_path('uploads/' . $user->image))) {
                $user->avatar = asset('uploads/' . $user->image);
            } else {
                $user->avatar = asset('uploads/default-avatar.png'); // Default image
            }
            return $user;
        });

        return response()->json(['data' => $users], 200);
    }



}
