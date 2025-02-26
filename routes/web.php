<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Http\Controllers\FaceRecognitionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Backend\Users\UsersController;
use App\Http\Controllers\Backend\Setting\SettingsController;
use App\Http\Controllers\Backend\Area\AreaController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\Backend\Profile\ProfileController;
use App\Http\Controllers\Backend\Attendance\AttendanceController;
use App\Http\Controllers\Backend\Event\EventController;
use App\Http\Controllers\Backend\Shift\ShiftController;
use App\Http\Controllers\Backend\Analytic\AnalyticsController;
use App\Http\Controllers\Utils\Activity\ReinputKeyController;

Route::get('/api/users/avatar', [UsersController::class, 'getUsersWithAvatar']);


Route::get('/', function () {
    return view('auth.login');
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');

/*
|--------------------------------------------------------------------------
| administrator
|--------------------------------------------------------------------------
*/
Route::group(['middleware' => ['role:administrator']], function () {
    Route::get('/users', [UsersController::class, 'index'])->name('users');
    Route::get('/users/add', [UsersController::class, 'add'])->name('users.add');
    Route::post('/users/create', [UsersController::class, 'create'])->name('users.create');
    Route::get('/users/edit/{id}', [UsersController::class, 'edit'])->name('users.edit');
    Route::post('/users/update', [UsersController::class, 'update'])->name('users.update');
    Route::get('/users/delete/{id}', [UsersController::class, 'delete'])->name('users.delete');
    Route::get('/users/import', [UsersController::class, 'import'])->name('users.import');
    Route::post('/users/importData', [UsersController::class, 'importData'])->name('users.importData');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings/update', [SettingsController::class, 'update'])->name('settings.update');
    Route::get('/settings/downloadSettingsQrCode', [SettingsController::class, 'downloadSettingsQrCode'])->name('settings.downloadSettingsQrCode');

    Route::get('/areas', [AreaController::class, 'index'])->name('areas');
    Route::get('/areas/add', [AreaController::class, 'add'])->name('areas.add');
    Route::post('/areas/create', [AreaController::class, 'create'])->name('areas.create');
    Route::get('/areas/edit/{id}', [AreaController::class, 'edit'])->name('areas.edit');
    Route::post('/areas/update', [AreaController::class, 'update'])->name('areas.update');
    Route::get('/areas/delete/{id}', [AreaController::class, 'delete'])->name('areas.delete');
    Route::get('/areas/showAllDataLocation/{id}', [AreaController::class, 'showAllDataLocation'])->name('areas.showAllDataLocation');
    Route::post('/areas/storeLocation', [AreaController::class, 'storeLocation'])->name('areas.storeLocation');
    Route::post('/areas/deleteLocationTable', [AreaController::class, 'deleteLocationTable'])->name('areas.deleteLocationTable');
});

/*
|--------------------------------------------------------------------------
| administrator|admin|editor|guest
|--------------------------------------------------------------------------
*/
Route::group(['middleware' => ['role:administrator|admin|staff|guest']], function () {
    Route::get('/checkProductVerify', [MainController::class, 'checkProductVerify'])->name('checkProductVerify');

    Route::get('/profile/details', [ProfileController::class, 'details'])->name('profile.details');
    Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
});
Route::get('/face-recognition', [FaceRecognitionController::class, 'index'])->name('face.recognition');


/*
|--------------------------------------------------------------------------
| administrator|admin|staff
|--------------------------------------------------------------------------
*/
Route::group(['middleware' => ['role:administrator|admin|staff']], function () {
    Route::get('/attendances', [AttendanceController::class, 'index'])->name('attendances');

    Route::get('/events', [EventController::class, 'index'])->name('events');
    Route::get('/events/getAllDataEvent', [EventController::class, 'getAllDataEvent'])->name('events.getAllDataEvent');
    Route::post('/events/create', [EventController::class, 'create'])->name('events.create');
    Route::post('/events/update', [EventController::class, 'update'])->name('events.update');
    Route::post('/events/delete/{id}', [EventController::class, 'delete'])->name('events.delete');
});

/*
|--------------------------------------------------------------------------
| administrator|admin
|--------------------------------------------------------------------------
*/
Route::group(['middleware' => ['role:administrator|admin']], function () {
    Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts');
    Route::get('/shifts/add', [ShiftController::class, 'add'])->name('shifts.add');
    Route::post('/shifts/create', [ShiftController::class, 'create'])->name('shifts.create');
    Route::get('/shifts/edit/{id}', [ShiftController::class, 'edit'])->name('shifts.edit');
    Route::post('/shifts/update', [ShiftController::class, 'update'])->name('shifts.update');
    Route::get('/shifts/delete/{id}', [ShiftController::class, 'delete'])->name('shifts.delete');

    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
});

Route::post('reinputkey/index/{code}', [ReinputKeyController::class, 'index']);
