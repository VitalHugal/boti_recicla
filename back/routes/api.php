<?php

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ParticipationController;
use App\Http\Controllers\ProductController;
use App\Models\Participation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/', function () {
    return 'api on';
});

Route::post('/register', [RegisterController::class, 'register']);

Route::middleware(['access'])->group(function () {

    //totem
    Route::get('/check-queue', [ParticipationController::class, 'participationActive']);
    Route::post('/finish-all/{id}', [ParticipationController::class, 'finishAll']);
    Route::post('/send-results/{id}', [ParticipationController::class, 'sendResultParticipationAndFinishedInteraction']);
    Route::get('/check-finish-weight/{id}', [ParticipationController::class, 'checkFinishedParticipation']);
    Route::post('/confirmation-weighing/{id}', [ParticipationController::class, 'confirmationWeighing']); //new
    Route::post('/finish-interaction/{id}', [ParticipationController::class, 'finishInteraction']); //new

    //mobile
    Route::post('/start-weighing/{id}', [ParticipationController::class, 'initialWeghing']);
    Route::post('/finish-weighing/{id}', [ParticipationController::class, 'finishedWeghing']);
    Route::get('/check-results/{id}', [ParticipationController::class, 'getResultsParticipation']);
});

//api-extern
Route::middleware(['token'])->group(function () {
    Route::get('/check-credits', [ParticipationController::class, 'checkCreditsAllParticipation']);
    Route::post('/redeem', [ParticipationController::class, 'redeemProducts']);
    Route::get('/get-all-products', [ProductController::class, 'getAllProducts']);
});