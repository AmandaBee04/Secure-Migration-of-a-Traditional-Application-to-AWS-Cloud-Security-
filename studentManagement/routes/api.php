<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\LecturerController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| auth:sanctum replaced with raw.auth throughout to avoid SIGSEGV caused
| by HasApiTokens model boot on ECS Fargate (PHP 8.2 + Debian Bookworm).
|
| raw.auth validates the Bearer token via DB::table('personal_access_tokens')
| and sets raw_user / raw_role on $request->attributes — no Eloquent boot.
|
*/

// ALB health check — no auth required
Route::get('/health', fn() => response()->json(['status' => 'ok']));

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['raw.auth'])->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::middleware(['raw.auth:admin'])->group(function () {
    Route::get('/students',         [AdminController::class, 'getStudent']);
    Route::post('/students',        [AdminController::class, 'addStudent']);
    Route::put('/students/{id}',    [AdminController::class, 'updateStudent']);
    Route::delete('/students/{id}', [AdminController::class, 'deleteStudent']);

    Route::get('/lecturers',         [AdminController::class, 'getLecturer']);
    Route::post('/lecturers',        [AdminController::class, 'addLecturer']);
    Route::put('/lecturers/{id}',    [AdminController::class, 'updateLecturer']);
    Route::delete('/lecturers/{id}', [AdminController::class, 'deleteLecturer']);

    Route::get('/subjects',         [AdminController::class, 'getSubject']);
    Route::post('/subjects',        [AdminController::class, 'addSubject']);
    Route::put('/subjects/{id}',    [AdminController::class, 'updateSubject']);
    Route::delete('/subjects/{id}', [AdminController::class, 'deleteSubject']);
});

Route::middleware(['raw.auth:lecturer'])->group(function () {
    Route::post('/results',           [LecturerController::class, 'addResults']);
    Route::put('/results/{id}',       [LecturerController::class, 'updateResults']);
    Route::delete('/results/{id}',    [LecturerController::class, 'deleteResults']);

    Route::get('/student-results',       [LecturerController::class, 'viewResultsBySubject']);
    Route::get('/available-semesters',   [LecturerController::class, 'getAvailableSemesters']);
    Route::get('/student-subjects',      [LecturerController::class, 'getStudentsBySubject']);
    Route::get('/students-without-grade',[LecturerController::class, 'getStudentsWithoutGrades']);
});

Route::middleware(['raw.auth:student'])->group(function () {
    Route::get('/results/{id}',            [StudentController::class, 'viewResults']);
    Route::get('/student-semesters/{id}',  [StudentController::class, 'getStudentAvailableSemesters']);
});
