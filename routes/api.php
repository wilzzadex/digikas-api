<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
// middleware auth:api is used to protect the routes
Route::post('login', [AuthController::class, 'login']);
Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('send-message', [TransactionController::class, 'sendMessage']);
Route::middleware('auth:api')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('ocr-image', [TransactionController::class, 'ocrImage']);

    Route::prefix('account')->group(function () {
        Route::get('status',[UserController::class,'getStatus']);
        Route::post('update-profile',[UserController::class,'updateProfile']);
    });

    Route::prefix('kategori')->group(function () {
        Route::get('/', [MasterDataController::class, 'getKategori']);
        Route::post('/', [MasterDataController::class, 'createKategori']);
        Route::put('/{id}', [MasterDataController::class, 'updateKategori']);
        Route::delete('/{id}', [MasterDataController::class, 'deleteKategori']);
    });

    Route::prefix('homepage')->group(function () {
        Route::get('summary', [TransactionController::class, 'getSummay']);
        Route::get('latest-transaction', [TransactionController::class, 'getLatestTransaction']);
    });

    Route::prefix('transaction')->group(function () {
        Route::get('get-category', [TransactionController::class, 'getCategory']);
        Route::post('/', [TransactionController::class, 'createTransaction']);
        Route::get('history', [TransactionController::class, 'getHistory']);
    });

    Route::prefix('report')->group(function () {
        Route::get('harian', [TransactionController::class, 'getReportHarian']);
        Route::get('bulanan', [TransactionController::class, 'getReportBulanan']);
        Route::get('mingguan', [TransactionController::class, 'getReportMingguan']);
    });
});
