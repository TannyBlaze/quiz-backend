<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\QuestionController;

use Illuminate\Support\Facades\DB;

Route::get('/test-db', function () {
    try {
        DB::connection()->getMongoClient(); // force connection

        return response()->json([
            'status' => 'success',
            'message' => 'MongoDB connected successfully'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('custom.auth')->group(function () {

    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // 📚 COURSES
    Route::get('/courses', [QuestionController::class, 'index']);
    Route::get('/courses/{id}', [QuestionController::class, 'show']);
    Route::get('/courses/{id}/quiz', [QuestionController::class, 'quiz']);
    Route::post('/courses/{id}/submit', [QuestionController::class, 'submitQuiz']);

    Route::middleware('role:admin,setter')->group(function () {
        Route::post('/courses', [QuestionController::class, 'store']);
        Route::put('/courses/{id}', [QuestionController::class, 'update']);
        Route::delete('/courses/{id}', [QuestionController::class, 'destroy']);
        Route::patch('/courses/{id}/toggle', [QuestionController::class, 'togglePublic']);
        Route::get('/courses/{id}/attempts', [QuestionController::class, 'getAttempts']);
    });

    // 👑 ADMIN
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [AuthController::class, 'allUsers']);
        Route::patch('/users/{id}/role', [AuthController::class, 'updateRole']);
        Route::delete('/users/{id}', [AuthController::class, 'deleteUser']);
        Route::post('/users', [AuthController::class, 'createUser']);
        Route::patch('/users/{id}', [AuthController::class, 'updateUser']);
    });
});
