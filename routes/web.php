<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->role === 'superadmin' ? redirect('/admin') : redirect('/staff');
    }
    return redirect('/kiosk');
});

Route::middleware(['auth', 'role:superadmin'])->group(function () {
    Route::get('/admin', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');

    Route::get('/admin/polis', function () {
        return view('admin.polis');
    })->name('admin.polis');

    Route::get('/admin/users', function () {
        return view('admin.users');
    })->name('admin.users');

    Route::get('/admin/patients', function () {
        return view('admin.patients');
    })->name('admin.patients');
});

Route::middleware(['auth', 'role:staff,superadmin'])->group(function () {
    Route::get('/staff', function () {
        return view('admin.staff');
    })->name('staff.dashboard');
});

Route::view('/kiosk', 'kiosk-page');
Route::view('/display', 'display-page');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
