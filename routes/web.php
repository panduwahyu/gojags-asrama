<?php

use App\Http\Controllers\AsramaController;
use App\Http\Controllers\CateringController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\LeavePermissionController;
use App\Http\Controllers\RoomAllocationController;
use App\Http\Controllers\UserRoomController;
use App\Http\Controllers\RoomQrController;
use App\Http\Controllers\UserLaundryController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/up', fn () => response()->json(['status' => 'ok']));

// Session and CSRF protection are provided by the web middleware group.
Route::prefix('api/asrama')->group(function () {
    Route::get('/session', function (Request $request) {
        $user = $request->user();

        return response()->json([
            'csrfToken' => csrf_token(),
            'user' => $user instanceof User ? $user->only('id', 'name', 'email', 'role') : null,
        ])->header('Cache-Control', 'no-store');
    });
    Route::post('/login', [AsramaController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/logout', [AsramaController::class, 'logout'])->middleware('auth');
    Route::middleware(['auth', 'role:user'])->prefix('user')->group(function () {
        Route::get('/keluhan', [ComplaintController::class, 'index']);
        Route::post('/keluhan', [ComplaintController::class, 'store']);
        Route::get('/catering', [CateringController::class, 'index']);
        Route::post('/catering/{id}/rating', [CateringController::class, 'rate'])->whereNumber('id');
        Route::get('/laundry', [UserLaundryController::class, 'index']);
        Route::post('/laundry', [UserLaundryController::class, 'store']);
        Route::get('/izin-keluar', [LeavePermissionController::class, 'index']);
        Route::post('/izin-keluar', [LeavePermissionController::class, 'store']);
        Route::patch('/izin-keluar/{id}', [LeavePermissionController::class, 'update'])->whereNumber('id');
        Route::get('/kamar', [UserRoomController::class, 'show']);
        Route::post('/kamar/scan', [UserRoomController::class, 'scan'])->middleware('throttle:10,1');
        Route::post('/kamar/check-in', [UserRoomController::class, 'checkIn'])->middleware('throttle:10,1');
        Route::post('/kamar/check-out', [UserRoomController::class, 'checkOut'])->middleware('throttle:10,1');
    });
    Route::get('/welcome', [AsramaController::class, 'welcome'])->middleware(['auth', 'role:user']);
    Route::middleware(['auth', 'role:admin'])->group(function () {
        Route::get('/keluhan', [ComplaintController::class, 'index']);
        Route::patch('/keluhan/{id}', [ComplaintController::class, 'resolve'])->whereNumber('id');
        Route::get('/catering', [CateringController::class, 'index']);
        Route::post('/catering', [CateringController::class, 'store']);
        Route::get('/izin-keluar', [LeavePermissionController::class, 'index']);
        Route::patch('/izin-keluar/{id}', [LeavePermissionController::class, 'review'])->whereNumber('id');
        Route::post('/kamar/qr', [RoomQrController::class, 'store'])->middleware('throttle:10,1');
        Route::post('/kamar/sync', [RoomAllocationController::class, 'sync']);
        Route::post('/kamar/allocate', [RoomAllocationController::class, 'allocate']);
        Route::patch('/allocation-users/{id}', [RoomAllocationController::class, 'profile'])->whereNumber('id');
        Route::get('/dashboard', [AsramaController::class, 'index']);
        Route::get('/{page}', [AsramaController::class, 'index'])->whereIn('page', ['kamar', 'laundry']);
        Route::post('/{category}', [AsramaController::class, 'store'])->whereIn('category', ['laundry']);
        Route::patch('/{category}/{id}', [AsramaController::class, 'update'])->whereIn('category', ['laundry', 'catering'])->whereNumber('id');
    });
});

Route::get('/', function (Request $request) {
    $user = $request->user();

    return redirect($user instanceof User ? '/'.$user->getAttribute('role') : '/login');
});
Route::get('/login', [AsramaController::class, 'index'])->defaults('page', 'login')->name('login');
Route::post('/login', [AsramaController::class, 'login'])->middleware('throttle:5,1');
Route::post('/logout', [AsramaController::class, 'logout'])->middleware('auth');
Route::get('/user', [AsramaController::class, 'welcome'])->middleware(['auth', 'role:user']);
Route::middleware(['auth', 'role:user'])->prefix('user')->group(function () {
    Route::post('/keluhan', [ComplaintController::class, 'store']);
    Route::get('/catering', [CateringController::class, 'index']);
    Route::post('/catering/{id}/rating', [CateringController::class, 'rate'])->whereNumber('id');
    Route::get('/laundry', [UserLaundryController::class, 'index']);
    Route::post('/laundry', [UserLaundryController::class, 'store']);
    Route::post('/izin-keluar', [LeavePermissionController::class, 'store']);
    Route::patch('/izin-keluar/{id}', [LeavePermissionController::class, 'update'])->whereNumber('id');
    Route::get('/kamar', [UserRoomController::class, 'show']);
    Route::post('/kamar/scan', [UserRoomController::class, 'scan'])->middleware('throttle:10,1');
    Route::post('/kamar/check-in', [UserRoomController::class, 'checkIn'])->middleware('throttle:10,1');
    Route::post('/kamar/check-out', [UserRoomController::class, 'checkOut'])->middleware('throttle:10,1');
    Route::get('/{service}', [UserRoomController::class, 'service'])->whereIn('service', ['laundry', 'catering']);
});
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::patch('/keluhan/{id}', [ComplaintController::class, 'resolve'])->whereNumber('id');
    Route::get('/catering', [CateringController::class, 'index']);
    Route::post('/catering', [CateringController::class, 'store']);
    Route::patch('/izin-keluar/{id}', [LeavePermissionController::class, 'review'])->whereNumber('id');
    Route::post('/kamar/qr', [RoomQrController::class, 'store'])->middleware('throttle:10,1');
        Route::post('/kamar/sync', [RoomAllocationController::class, 'sync']);
        Route::post('/kamar/allocate', [RoomAllocationController::class, 'allocate']);
    Route::patch('/allocation-users/{id}', [RoomAllocationController::class, 'profile'])->whereNumber('id');
    Route::get('/admin', [AsramaController::class, 'index']);
    Route::get('/{page}', [AsramaController::class, 'index'])->whereIn('page', ['kamar', 'laundry']);
    Route::post('/{category}', [AsramaController::class, 'store'])->whereIn('category', ['laundry']);
    Route::patch('/{category}/{id}', [AsramaController::class, 'update'])->whereIn('category', ['laundry', 'catering'])->whereNumber('id');
    Route::get('/pengumuman', function () {
        return view('pengumuman');
    });
    Route::get('/checkin', function () {
        return view('checkin');
    });
});
