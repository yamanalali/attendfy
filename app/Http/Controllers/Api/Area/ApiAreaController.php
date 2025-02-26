<?php

namespace App\Http\Controllers\Api\Area;

use App\Http\Controllers\Controller;
use App\Models\Area;

class ApiAreaController extends Controller
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
     * API data Area
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $dataArea = Area::all('id', 'name');
        if ($dataArea->count() > 0) {
            $data = [
                'message' => 'success',
                'area' => $dataArea
            ];
            return response()->json($data);
        }

        $data = [
            'message' => 'empty',
        ];
        return response()->json($data);
    }
}
