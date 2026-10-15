<?php

use App\Http\Controllers\CateringMenuController;
use App\Http\Controllers\CateringRatingController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaundryOrderController;
use App\Http\Controllers\LeavePermitController;
use App\Http\Controllers\MyRoomController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\RoomAllocationController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomPlacementController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

Route::get('session', [SessionController::class, 'show']);
Route::post('login', [SessionController::class, 'store'])->middleware('throttle:login');

Route::middleware('auth')->group(function () {
    Route::post('logout', [SessionController::class, 'destroy']);
    Route::put('password', [PasswordController::class, 'update']);

    // Dibaca kedua peran; isinya disaring oleh scope visibleTo().
    Route::get('leave-permits', [LeavePermitController::class, 'index']);
    Route::get('complaints', [ComplaintController::class, 'index']);
    Route::get('laundry-orders', [LaundryOrderController::class, 'index']);
    Route::get('catering-menus', [CateringMenuController::class, 'index']);

    // Ekspor mengikuti filter tabel (q, sort, direction); format=xlsx atau pdf.
    Route::get('leave-permits/export', [LeavePermitController::class, 'export']);
    Route::get('complaints/export', [ComplaintController::class, 'export']);
    Route::get('laundry-orders/export', [LaundryOrderController::class, 'export']);
    Route::get('catering-menus/export', [CateringMenuController::class, 'export']);

    // Penghuni hanya untuk keluhannya sendiri (404 bila bukan miliknya); admin untuk semua.
    Route::patch('complaints/{complaint}/reopen', [ComplaintController::class, 'reopen']);

    Route::middleware('role:resident')->group(function () {
        Route::get('my-room', [MyRoomController::class, 'show']);
        Route::post('my-room/check-in', [MyRoomController::class, 'checkIn']);
        Route::post('my-room/check-out', [MyRoomController::class, 'checkOut']);
        Route::post('leave-permits', [LeavePermitController::class, 'store'])->middleware('staying');
        Route::patch('leave-permits/{permit}', [LeavePermitController::class, 'update'])->middleware('staying');
        Route::patch('leave-permits/{permit}/cancel', [LeavePermitController::class, 'cancel']);
        Route::post('complaints', [ComplaintController::class, 'store']);
        Route::post('laundry-orders', [LaundryOrderController::class, 'store'])->middleware('staying');
        Route::patch('laundry-orders/{order}/cancel', [LaundryOrderController::class, 'cancel']);
        Route::put('catering-menus/{menu}/rating', [CateringRatingController::class, 'update'])->middleware('staying');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('dashboard', DashboardController::class);
        Route::get('rooms', [RoomController::class, 'index']);
        Route::get('rooms/export', [RoomController::class, 'export']);
        Route::post('rooms/allocate', RoomAllocationController::class);
        Route::patch('rooms/{room}', [RoomController::class, 'update']);
        Route::post('rooms/{room}/assign', [RoomPlacementController::class, 'assign']);
        Route::post('rooms/{room}/move', [RoomPlacementController::class, 'move']);

        Route::get('residents', [ResidentController::class, 'index']);
        Route::get('residents/export', [ResidentController::class, 'export']);
        Route::post('residents', [ResidentController::class, 'store']);
        Route::post('residents/import', [ResidentController::class, 'import']);
        Route::patch('residents/{resident}', [ResidentController::class, 'update']);
        Route::post('residents/{resident}/reset-password', [ResidentController::class, 'resetPassword']);
        Route::post('residents/{resident}/check-out', [ResidentController::class, 'checkOut']);
        Route::post('residents/{resident}/cancel-check-out', [ResidentController::class, 'cancelCheckOut']);
        Route::delete('residents/{resident}/room', [ResidentController::class, 'unassign']);

        Route::patch('leave-permits/{permit}/review', [LeavePermitController::class, 'review']);
        Route::patch('complaints/{complaint}/progress', [ComplaintController::class, 'progress']);
        Route::patch('complaints/{complaint}/resolve', [ComplaintController::class, 'resolve']);
        Route::patch('laundry-orders/{order}', [LaundryOrderController::class, 'update']);
        Route::post('catering-menus', [CateringMenuController::class, 'store']);
        Route::patch('catering-menus/{menu}', [CateringMenuController::class, 'update']);
        Route::delete('catering-menus/{menu}', [CateringMenuController::class, 'destroy']);
    });
});
