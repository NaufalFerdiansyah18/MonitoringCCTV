<?php

use App\Http\Controllers\AlarmController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CameraController;
use App\Http\Controllers\CctvTestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DvrController;
use App\Http\Controllers\LiveviewController;
use App\Http\Controllers\PtzController;
use App\Http\Controllers\RecordingController;
use App\Http\Controllers\TechnicalGroupController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CctvTestController::class, 'index']);
Route::post('/cctv/test', [CctvTestController::class, 'start']);
Route::get('/cctv/status', [CctvTestController::class, 'status']);
Route::post('/cctv/stop', [CctvTestController::class, 'stop']);

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/liveview/start', [LiveviewController::class, 'start'])->name('liveview.start');
    Route::post('/liveview/stop', [LiveviewController::class, 'stop'])->name('liveview.stop');
    Route::get('/liveview/status', [LiveviewController::class, 'status'])->name('liveview.status');

    Route::post('/recordings/start', [RecordingController::class, 'start'])->name('recordings.start');
    Route::post('/recordings/stop', [RecordingController::class, 'stop'])->name('recordings.stop');
    Route::get('/recordings/status', [RecordingController::class, 'status'])->name('recordings.status');
    Route::get('/recordings', [RecordingController::class, 'index'])->name('recordings.index');
    Route::get('/recordings/{recording}/playlist', [RecordingController::class, 'playlist'])->name('recordings.playlist');
    Route::get('/recordings/{recording}/export', [RecordingController::class, 'export'])->name('recordings.export');

    Route::post('/ptz/{camera}', [PtzController::class, 'send'])->name('ptz.send');

    Route::post('/alarms', [AlarmController::class, 'store'])->name('alarms.store');

    Route::middleware('role.superadmin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::resource('technical-groups', TechnicalGroupController::class)->except(['show']);
        Route::resource('units', UnitController::class)->except(['show']);
        Route::resource('dvrs', DvrController::class)->except(['show']);
        Route::resource('dvrs.cameras', CameraController::class)->except(['show']);

        Route::get('/alarms', [AlarmController::class, 'index'])->name('alarms.index');
        Route::post('/alarms/{alarm}/seen', [AlarmController::class, 'seen'])->name('alarms.seen');
    });
});
