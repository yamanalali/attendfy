<?php
namespace App\Http\Controllers\Backend\Event;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Utils\Activity\SaveActivityLogController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Event;

class EventController extends Controller
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

    public function getAllDataEvent(){
        $allData = Event::all();
        $data = [];
        
        if (count($allData) > 0) {
            for ($i = 0; $i < count($allData); $i++) {
                $data[$i] = [
                    "eventid" => $allData[$i]->id,
                    "title" => $allData[$i]->title,
                    "description" => $allData[$i]->desc,
                    "start" => $allData[$i]->start_date->format('Y-m-d'),
                    "end" => $allData[$i]->end_date->format('Y-m-d'),
                ];
            }
        }

        return response()->json($data);
    }

    /**
     * Show the application dashboard.
     * 
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        return view('backend.events.index');
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $new = $request->all();
        $this->validator($new, 'create')->validate();
        try {
            $createNew = Event::create($new);
            if ($createNew) {

                $createNew->save();

                // Save log
                $controller = new SaveActivityLogController();
                $controller->saveLog($new, "Create new event");

                $data = [
                    'code' => 1,
                    'message' => 'Success add event!',
                    'eventid' => $createNew->id
                ];
                return response()->json($data);
            }

            // Create is failed
            $data = [
                'code' => 0,
                'message' => 'Sorry, error when adding event.',
            ];
            return response()->json($data);
        } catch (\Exception $e) {
            // Create is failed
            $data = [
                'code' => 0,
                'message' => 'Error: '. $e,
            ];
            return response()->json($data);
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
     * Update the specified resource in storage.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $new = $request->all();
        try {
            $currentData = Event::find($request->get('id'));
            if ($currentData) {
                $this->validator($new, 'update')->validate();

                // Update
                $currentData->update($new);

                // Save log
                $controller = new SaveActivityLogController();
                $controller->saveLog($new, "Update event");

                $data = [
                    'code' => 1,
                    'message' => 'Success update event!',
                ];
                return response()->json($data);
            }

            // If update is failed
            $data = [
                'code' => 0,
                'message' => 'Sorry, error when updating event.',
            ];
            return response()->json($data);
        } catch (\Exception $e) {
            // If update is failed
            $data = [
                'code' => 0,
                'message' => 'Error: '. $e,
            ];
            return response()->json($data);
        }
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @param mixed $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id)
    {
        try {
            // Delete
            $data = Event::find($id);
            $data->delete();

            // Save log
            $controller = new SaveActivityLogController();
            $controller->saveLog($data->toArray(), "Delete event");

            //delete success
            $data = [
                'code' => 1,
                'message' => 'Success delete event!',
            ];
            return response()->json($data);
        } catch (\Exception $e) {
            // delete failed
            $data = [
                'code' => 0,
                'message' => 'Sorry, error when deleting event.',
            ];
            return response()->json($data);
        } catch (\Illuminate\Database\QueryException $e) {
            $data = [
                'code' => 0,
                'message' => 'Error: '. $e,
            ];
            return response()->json($data);
        }
    }
}
