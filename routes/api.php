<?php

use App\Http\Controllers\CateringMenuController;
use App\Http\Controllers\CateringRatingController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaundryOrderController;
use App\Http\Controllers\LeavePermitController;
use App\Http\Controllers\MyRoomController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\RoomAllocationController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

Route::get('session', [SessionController::class, 'show']);
Route::post('login', [SessionController::class, 'store'])->middleware('throttle:login');

Route::middleware('auth')->group(function () {
    Route::post('logout', [SessionController::class, 'destroy']);

    // Dibaca kedua peran; isinya disaring oleh scope visibleTo().
    Route::get('leave-permits', [LeavePermitController::class, 'index']);
    Route::get('complaints', [ComplaintController::class, 'index']);
    Route::get('laundry-orders', [LaundryOrderController::class, 'index']);
    Route::get('catering-menus', [CateringMenuController::class, 'index']);

    Route::middleware('role:resident')->group(function () {
        Route::get('my-room', [MyRoomController::class, 'show']);
        Route::post('my-room/check-in', [MyRoomController::class, 'checkIn']);
        Route::post('my-room/check-out', [MyRoomController::class, 'checkOut']);
        Route::post('leave-permits', [LeavePermitController::class, 'store']);
        Route::patch('leave-permits/{permit}', [LeavePermitController::class, 'update']);
        Route::post('complaints', [ComplaintController::class, 'store']);
        Route::post('laundry-orders', [LaundryOrderController::class, 'store']);
        Route::put('catering-menus/{menu}/rating', [CateringRatingController::class, 'update']);
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('dashboard', DashboardController::class);
        Route::get('rooms', [RoomController::class, 'index']);
        Route::post('rooms/allocate', RoomAllocationController::class);
        Route::get('residents', [ResidentController::class, 'index']);
        Route::patch('residents/{resident}', [ResidentController::class, 'update']);
        Route::patch('leave-permits/{permit}/review', [LeavePermitController::class, 'review']);
        Route::patch('complaints/{complaint}/resolve', [ComplaintController::class, 'resolve']);
        Route::patch('laundry-orders/{order}', [LaundryOrderController::class, 'update']);
        Route::post('catering-menus', [CateringMenuController::class, 'store']);
        Route::patch('catering-menus/{menu}', [CateringMenuController::class, 'update']);
    });
});
