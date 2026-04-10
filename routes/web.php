<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Registrar\RegistrarController;
use App\Http\Controllers\Student\StudentController;
use App\Http\Controllers\Lecture\LectureController;
use App\Http\Controllers\Hod\HodController;
use Termwind\Components\Li;

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

// Student routes
Route::middleware(['auth.session', 'student'])->group(function () {
    Route::get('/student/dashboard',  fn() => Inertia::render('Student/Dashboard'))->name('student.dashboard');
    Route::get('/student/myinfo',     [StudentController::class, 'MyInfo'])->name('student.Myinfo');
    Route::get('/student/feedback',   [StudentController::class, 'FeedBack'])->name('student.FeedBack');
    Route::post('/student/feedback',  [StudentController::class, 'submitFeedback'])->name('student.feedback.submit');
    Route::get('/student/track',      [StudentController::class, 'trackFeedback'])->name('student.feedback.track');
     Route::post('/student/followup',  [StudentController::class, 'sendFollowup'])->name('student.feedback.followup');
});

// Lecturer routes
Route::middleware(['auth.session', 'lecture'])->group(function () {
    Route::get('/lecturer/dashboard',  fn() => Inertia::render('Lecture/Dashboard'))->name('lecturer.dashboard');
    Route::get('/lecturer/feedback',   [LectureController::class, 'FeedBack'])->name('lecture.FeedBack');
    Route::post('/lecturer/feedback',  [LectureController::class, 'submitFeedback'])->name('lecture.feedback.submit');
    Route::get('/lecturer/track',      [LectureController::class, 'trackFeedback'])->name('lecture.feedback.track');
    Route::post('/lecturer/followup',  [LectureController::class, 'sendFollowup'])->name('lecture.feedback.followup');
});

// Admin routes
Route::middleware(['auth.session', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard',   [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/ManageData',  [AdminController::class, 'ManageData'])->name('admin.ManageData');
    // Proxy to Auth Service
    Route::post('/faculties',           [AdminController::class, 'storeFaculty'])->name('admin.faculties.store');
    Route::post('/departments',         [AdminController::class, 'storeDepartment'])->name('admin.departments.store');
    Route::post('/programs',            [AdminController::class, 'storeProgram'])->name('admin.programs.store');
});

//registrar routes
Route::middleware(['auth.session', 'registrar'])->group(function () {
    Route::get('/registrar/dashboard',  [RegistrarController::class, 'dashboard'])->name('registrar.dashboard');
    Route::get('/registrar/ManageUser', [RegistrarController::class, 'ManageUser'])->name('registrar.ManageUser');
    Route::post('/registrar/import/students', [RegistrarController::class, 'importStudents'])->name('registrar.import.students');
    Route::post('/registrar/import/staff',    [RegistrarController::class, 'importStaff'])->name('registrar.import.staff');
});

Route::middleware(['auth.session', 'hod'])->group(function () {
    Route::get('/hod/dashboard',             [HodController::class, 'dashboard'])->name('hod.dashboard');
    Route::get('/hod/feedbacks',             [HodController::class, 'feedbacks'])->name('hod.feedbacks');
    Route::get('/hod/feedbacks/{id}',        [HodController::class, 'show'])->name('hod.feedbacks.show');
    Route::post('/hod/feedbacks/{id}/respond',  [HodController::class, 'respond'])->name('hod.feedbacks.respond');
    Route::post('/hod/feedbacks/{id}/escalate', [HodController::class, 'escalate'])->name('hod.feedbacks.escalate');
    Route::post('/hod/feedbacks/{id}/resolve',  [HodController::class, 'resolve'])->name('hod.feedbacks.resolve');
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