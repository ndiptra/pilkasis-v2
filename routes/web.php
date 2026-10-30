<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\ElectionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\SchoolProfileController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoterAccessController;
use App\Http\Controllers\VoterController;
use App\Http\Controllers\VoterTransferController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::get('/', HomeController::class)->name('dashboard');

Route::get('/voter/login', [VoterAccessController::class, 'create'])->name('voter.login');
Route::post('/voter/login', [VoterAccessController::class, 'store'])->name('voter.authenticate');
Route::post('/voter/logout', [VoterAccessController::class, 'logout'])->name('voter.logout');
Route::get('/vote/thanks', [VoterAccessController::class, 'thanks'])->name('voter.thanks');

Route::middleware('voter.session')->group(function () {
    Route::get('/vote', [VoterAccessController::class, 'ballot'])->name('voter.ballot');
    Route::post('/vote', [VoterAccessController::class, 'vote'])->name('voter.vote');
});

Route::middleware('auth')->group(function () {
    Route::middleware('data.editable')->group(function () {
        Route::get('/voters/template', [VoterTransferController::class, 'template'])->name('voters.template');
        Route::get('/voters/export', [VoterTransferController::class, 'export'])->name('voters.export');
        Route::post('/voters/import', [VoterTransferController::class, 'import'])->name('voters.import');
        Route::resource('voters', VoterController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('candidates', CandidateController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    Route::get('/results', [ResultController::class, 'index'])->middleware('results.visible')->name('results.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::middleware('superadmin')->group(function () {
        Route::get('/school-profile', [SchoolProfileController::class, 'edit'])->name('school-profile.edit');
        Route::put('/school-profile', [SchoolProfileController::class, 'update'])->name('school-profile.update');

        Route::resource('elections', ElectionController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('/elections/{election}/activate', [ElectionController::class, 'activate'])->name('elections.activate');
        Route::post('/elections/{election}/deactivate', [ElectionController::class, 'deactivate'])->name('elections.deactivate');

        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

        Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
