<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\KodeController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;

// Route publik untuk akses detail item via QR code
Route::get('/items/qr/{id}', [ItemController::class, 'showPublic']);

Route::apiResource('roles', RoleController::class);
Route::post('/login', [AuthController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // 🔑 Custom Loan actions – taruh di ATAS resource agar tidak ketimpa
    Route::get('/loans/active',   [LoanController::class, 'active']);
    Route::get('/loans/history',  [LoanController::class, 'history']);
    Route::get('/loans/all', [LoanController::class, 'all']);
    Route::post('/loans/{loan}/approve',  [LoanController::class, 'approve']);
    Route::post('/loans/{loan}/reject',   [LoanController::class, 'reject']);
    Route::post('/loans/{loan}/returned', [LoanController::class, 'returned']);
    Route::get('/items/{id}/loans', [LoanController::class, 'byItem']);


    // ✅ Resource
    Route::apiResource('kodes', KodeController::class);
    Route::apiResource('items', ItemController::class);
    Route::apiResource('loans', LoanController::class);
    
    Route::apiResource('users', UserController::class);


});
