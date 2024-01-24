<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AccountController;

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

Route::get( '/accounts/lookup', [ AccountController::class, 'lookup' ] );
Route::get( '/accounts/{id}', [ AccountController::class, 'show' ] );

Route::middleware( 'cors' )->group( function () {
    Route::get( '/{username}/profile', [ AccountController::class, 'profile' ] );
} );

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
