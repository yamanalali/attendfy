<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use App\Http\Controllers\Api\Auth\ApiAuthController;
use App\Http\Controllers\Api\Area\ApiAreaController;
use App\Http\Controllers\Api\Attendance\ApiAttendanceController;
use App\Helpers\Helper;
use App\Http\Controllers\Backend\Attendance\AttendanceController;
use App\Http\Controllers\Backend\Users\UsersController;


Route::post('attendances', [AttendanceController::class, 'store']);
Route::put('attendances/checkout', [AttendanceController::class, 'checkOut']);

Route::post('login', [ApiAuthController::class, 'login']);
Route::group(['middleware' => ['auth:api']], function () {
    Route::get('area/index', [ApiAreaController::class, 'index']);
    Route::post('attendance/apiSaveAttendance', [ApiAttendanceController::class, 'apiSaveAttendance']);
});

Route::get('/helper/{code}', function ($code) {return Helper::checkingCode($code);});
Route::get('/helper', function () {return Helper::getInfo();});
Route::get('/write', function () {return Helper::write();});


