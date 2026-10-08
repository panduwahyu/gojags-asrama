<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/pengumuman', function () {
    return view('pengumuman');
});
Route::get('/checkin', function () {
    return view('checkin');
});