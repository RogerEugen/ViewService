<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Student\StudentController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');


// Change password — only accessible after first login
Route::middleware('auth.session')->group(function () {
    Route::get('/change-password',  [AuthenticatedSessionController::class, 'showChangePassword'])->name('password.change');
    Route::post('/change-password', [AuthenticatedSessionController::class, 'updatePassword'])->name('password.update');
});


Route::middleware('auth.session')->group(function () {
    Route::get('/student/dashboard',   fn() => Inertia::render('Student/Dashboard'))->name('student.dashboard');
    Route::get('/lecturer/dashboard',  fn() => Inertia::render('Lecture/Dashboard'))->name('lecturer.dashboard');
    Route::get('/hod/dashboard',       fn() => Inertia::render('Hod/Dashboard'))->name('hod.dashboard');
    Route::get('/dean/dashboard',      fn() => Inertia::render('Dean/Dashboard'))->name('dean.dashboard');
    Route::get('/rector/dashboard',    fn() => Inertia::render('Rector/Dashboard'))->name('rector.dashboard');
    Route::get('/registrar/dashboard', fn() => Inertia::render('Register/Dashboard'))->name('registrar.dashboard');
    Route::get('/admin/dashboard',     fn() => Inertia::render('Admin/Dashboard'))->name('admin.dashboard');
});

Route::middleware('auth.session','student')->group(function () {
    Route::get('/student/myinfo', [StudentController::class, 'MyInfo'])->name('student.Myinfo');

});

Route::middleware('auth.session','admin')->group(function () {
    Route::get('/admin/ManageData', [AdminController::class, 'ManageData'])->name('admin.ManageData');
});



Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

//login logout
Route::middleware('auth.session')->post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');    

// require __DIR__.'/auth.php';
