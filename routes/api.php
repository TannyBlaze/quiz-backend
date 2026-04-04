<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\QuestionController;
use Illuminate\Support\Facades\DB;
use MongoDB\Client;

Route::get('/test-db', function () {
    try {
        \App\Models\User::first();
        return response()->json([
            'status' => 'success',
            'message' => 'DB working'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
});

Route::get('/ping', function () {
    return response()->json(['status' => 'ok']);
});

Route::get('/env-test', function () {
    return response()->json([
        'app_env' => env('APP_ENV'),
        'db_connection' => env('DB_CONNECTION'),
        'mongo_uri_exists' => env('MONGODB_URI') ? true : false,
    ]);
});

Route::get('/mongo-test', function () {
    try {
        $client = new Client(env('MONGODB_URI'));
        $db = $client->selectDatabase(env('DB_DATABASE'));
        $collections = $db->listCollections();

        return response()->json([
            'status' => 'connected',
            'collections' => iterator_to_array($collections)
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
});

Route::get('/model-test', function () {
    try {
        $user = \App\Models\User::first();
        return response()->json([
            'status' => 'success',
            'user' => $user
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
});

Route::get('/collection-test', function () {
    try {
        $count = \App\Models\User::count();
        return response()->json([
            'status' => 'success',
            'count' => $count
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
});

Route::get('/debug-users', function () {
    try {
        $users = \App\Models\User::all();

        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
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
