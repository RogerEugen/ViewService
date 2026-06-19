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
use App\Http\Controllers\Dean\DeanController;
use App\Http\Controllers\Rector\RectorController;
use App\Http\Controllers\CommunicationController;
use App\Http\Controllers\FeedbackChatController;

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
    Route::get('/communications', [CommunicationController::class, 'index'])
        ->name('communications.index');
    Route::post('/communications', [CommunicationController::class, 'store'])
        ->name('communications.store');
    Route::get('/change-password',  [AuthenticatedSessionController::class, 'showChangePassword'])->name('password.change');
    Route::post('/change-password', [AuthenticatedSessionController::class, 'updatePassword'])->name('password.update');
});


Route::middleware('auth.session')->group(function () {
    Route::get('/student/dashboard',   [StudentController::class, 'dashboard'])->name('student.dashboard');
    Route::get('/lecturer/dashboard',  [LectureController::class, 'dashboard'])->name('lecturer.dashboard');
    Route::get('/hod/dashboard',       fn() => Inertia::render('Hod/Dashboard'))->name('hod.dashboard');
    Route::get('/dean/dashboard',      fn() => Inertia::render('Dean/Dashboard'))->name('dean.dashboard');
    Route::get('/rector/dashboard',    fn() => Inertia::render('Rector/Dashboard'))->name('rector.dashboard');
    Route::get('/registrar/dashboard', fn() => Inertia::render('Register/Dashboard'))->name('registrar.dashboard');
    Route::get('/admin/dashboard',     fn() => Inertia::render('Admin/Dashboard'))->name('admin.dashboard');
});

// Student routes
Route::middleware(['auth.session', 'student'])->group(function () {
    Route::get('/student/dashboard',  [StudentController::class, 'dashboard'])->name('student.dashboard');
    Route::get('/student/myinfo',     [StudentController::class, 'MyInfo'])->name('student.Myinfo');
    Route::get('/student/myinfo',     [StudentController::class, 'MyInfo'])->name('student.myinfo');
    Route::get('/student/feedback',   [StudentController::class, 'FeedBack'])->name('student.FeedBack');
    Route::get('/student/feedback',   [StudentController::class, 'FeedBack'])->name('student.feedback');
    Route::post('/student/feedback',  [StudentController::class, 'submitFeedback'])->name('student.feedback.submit');
    Route::get('/student/track',      [StudentController::class, 'trackFeedback'])->name('student.feedback.track');
    Route::get('/student/track',      [StudentController::class, 'trackFeedback'])->name('student.track');
    Route::post('/student/followup',  [StudentController::class, 'sendFollowup'])->name('student.feedback.followup');

    Route::get('/student/evaluations',  [StudentController::class, 'evaluations'])->name('student.evaluations');
    Route::post('/student/evaluations', [StudentController::class, 'submitEvaluation'])->name('student.evaluations.submit');
});

// Lecturer routes
Route::middleware(['auth.session', 'lecture'])->group(function () {
    Route::get('/lecturer/dashboard',  [LectureController::class, 'dashboard'])->name('lecturer.dashboard');
    Route::get('/lecturer/feedback',   [LectureController::class, 'FeedBack'])->name('lecture.FeedBack');
    Route::get('/lecturer/feedback',   [LectureController::class, 'FeedBack'])->name('lecture.feedback');
    Route::post('/lecturer/feedback',  [LectureController::class, 'submitFeedback'])->name('lecture.feedback.submit');
    Route::get('/lecturer/track',      [LectureController::class, 'trackFeedback'])->name('lecture.feedback.track');
    Route::get('/lecturer/track',      [LectureController::class, 'trackFeedback'])->name('lecture.track');
    Route::post('/lecturer/followup',  [LectureController::class, 'sendFollowup'])->name('lecture.feedback.followup');
    Route::get('/lecturer/evaluations', [LectureController::class, 'evaluationResults'])->name('lecture.evaluations');
    Route::get('/lecturer/rector-chat', [FeedbackChatController::class, 'lecturer'])->name('lecture.rector-chat');
    Route::post('/lecturer/rector-chat', [FeedbackChatController::class, 'lecturerSend'])->name('lecture.rector-chat.send');
});

// Admin routes
Route::middleware(['auth.session', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard',                       [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/ManageData',                      [AdminController::class, 'ManageData'])->name('admin.ManageData');
    Route::get('/feedbacks',                       [AdminController::class, 'feedbacks'])->name('admin.feedbacks');
    Route::get('/feedbacks/{id}',                  [AdminController::class, 'showFeedback'])->name('admin.feedbacks.show');
    Route::post('/feedbacks/{id}/respond',         [AdminController::class, 'respondFeedback'])->name('admin.feedbacks.respond');
    Route::post('/feedbacks/{id}/resolve',         [AdminController::class, 'resolveFeedback'])->name('admin.feedbacks.resolve');
    // Data management
    Route::post('/faculties',                      [AdminController::class, 'storeFaculty'])->name('admin.faculties.store');
    Route::post('/departments',                    [AdminController::class, 'storeDepartment'])->name('admin.departments.store');
    Route::post('/programs',                       [AdminController::class, 'storeProgram'])->name('admin.programs.store');
    Route::post('/categories',                     [AdminController::class, 'storeCategory'])->name('admin.categories.store');
    Route::put('/categories/{id}',                 [AdminController::class, 'updateCategory'])->name('admin.categories.update');
    Route::delete('/categories/{id}',              [AdminController::class, 'deleteCategory'])->name('admin.categories.delete');
    Route::post('/faculties/{id}/dean',            [AdminController::class, 'storeDean'])->name('admin.faculties.dean.store');

    Route::get('/evaluation-windows',          [AdminController::class, 'evaluationWindows'])->name('admin.evaluation-windows');
    Route::post('/evaluation-windows',         [AdminController::class, 'storeEvaluationWindow'])->name('admin.evaluation-windows.store');
    Route::post('/evaluation-windows/{id}/toggle', [AdminController::class, 'toggleEvaluationWindow'])->name('admin.evaluation-windows.toggle');
    Route::delete('/evaluation-windows/{id}',  [AdminController::class, 'deleteEvaluationWindow'])->name('admin.evaluation-windows.delete');
    Route::post('/departments/{id}/hod', [AdminController::class, 'storeHod'])->name('admin.departments.hod.store');
    Route::get('/analytics', [AdminController::class, 'analytics'])->name('admin.analytics');
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
    Route::get('/hod/evaluations', [HodController::class, 'evaluations'])->name('hod.evaluations');
});

Route::middleware(['auth.session', 'dean'])->group(function () {
    Route::get('/dean/dashboard',                 [DeanController::class, 'dashboard'])->name('dean.dashboard');
    Route::get('/dean/feedbacks',                 [DeanController::class, 'feedbacks'])->name('dean.feedbacks');
    Route::get('/dean/feedbacks/{id}',            [DeanController::class, 'show'])->name('dean.feedbacks.show');
    Route::post('/dean/feedbacks/{id}/respond',   [DeanController::class, 'respond'])->name('dean.feedbacks.respond');
    Route::post('/dean/feedbacks/{id}/escalate',  [DeanController::class, 'escalate'])->name('dean.feedbacks.escalate');
    Route::post('/dean/feedbacks/{id}/resolve',   [DeanController::class, 'resolve'])->name('dean.feedbacks.resolve');
    Route::get('/dean/evaluations', [DeanController::class, 'evaluations'])->name('dean.evaluations');
    Route::get('/dean/conduct-reviews', [DeanController::class, 'conductReviews'])->name('dean.conduct-reviews');
    Route::post('/dean/conduct-reviews/{id}/review', [DeanController::class, 'markConductReview'])->name('dean.conduct-reviews.mark');
});

Route::middleware(['auth.session', 'rector'])->group(function () {
    Route::get('/rector/dashboard',              [RectorController::class, 'dashboard'])->name('rector.dashboard');
    Route::get('/rector/feedbacks',              [RectorController::class, 'feedbacks'])->name('rector.feedbacks');
    Route::get('/rector/feedbacks/{id}',         [RectorController::class, 'show'])->name('rector.feedbacks.show');
    Route::post('/rector/feedbacks/{id}/respond',[RectorController::class, 'respond'])->name('rector.feedbacks.respond');
    Route::post('/rector/feedbacks/{id}/resolve',[RectorController::class, 'resolve'])->name('rector.feedbacks.resolve');
    Route::get('/rector/analytics', [RectorController::class, 'analytics'])->name('rector.analytics');
    Route::get('/rector/lecturer-chats', [FeedbackChatController::class, 'rector'])->name('rector.lecturer-chats');
    Route::post('/rector/lecturer-chats', [FeedbackChatController::class, 'rectorSend'])->name('rector.lecturer-chats.send');
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
