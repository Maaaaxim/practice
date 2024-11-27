<?php

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

Route::middleware('auth:sanctum')->group(function () {
    Route::group(['namespace' => 'App\Http\Controllers'], function () {

        Route::post('/calculations', [\App\Http\Controllers\CalculationController::class, 'store'])->name('calculations.store');
        Route::get('/calculations', [\App\Http\Controllers\CalculationController::class, 'index'])->name('calculations.index');

        Route::get('/products/popular', [\App\Http\Controllers\PopularProductController::class, 'index'])->name('products.popular');

        Route::post('/meals', [\App\Http\Controllers\MealController::class, 'store'])->name('meals.store');
        Route::get('/meals/{date}', [\App\Http\Controllers\MealController::class, 'index'])->name('meals.index');
        Route::put('/meals/{meal}', [\App\Http\Controllers\MealController::class, 'update'])->name('meals.update');
        Route::delete('/meals/{meal}', [\App\Http\Controllers\MealController::class, 'destroy'])->name('meals.destroy');

        Route::post('/user-meals', [\App\Http\Controllers\UsersMealController::class, 'store'])->name('user-meals.store');

        Route::get('/calendar/{date}', [\App\Http\Controllers\CalendarController::class, 'showRange'])->name('calendar.showRange');

        Route::get('/products/search', [\App\Http\Controllers\SearchProductController::class, 'search'])->name('meals.search');
        Route::get('/user', [\App\Http\Controllers\UserController::class, 'show'])->name('show');
    });
});

Route::group(['namespace' => 'App\Http\Controllers'], function () {
    Route::post('/caloriesEndPoint', [\App\Http\Controllers\CaloriesAPIBotController::class, 'store'])->name('calculations.store');
    Route::post('/caloriesEndPoint/saveProduct', [\App\Http\Controllers\CaloriesAPIBotController::class, 'saveProduct'])->name('calculations.saveProduct');
    Route::post('/caloriesEndPoint/saveFoodConsumption', [\App\Http\Controllers\CaloriesAPIBotController::class, 'saveFoodConsumption'])->name('calculations.saveFoodConsumption');
    Route::get('/caloriesEndPoint/showUserStats/{date}/{partOfDay?}', [\App\Http\Controllers\CaloriesAPIBotController::class, 'showUserStats'])->name('calculations.showUserStats');
    Route::post('/caloriesEndPoint/getTheMostRelevantProduct', [\App\Http\Controllers\CaloriesAPIBotController::class, 'getTheMostRelevantProduct'])->name('calculations.getTheMostRelevantProduct');
    Route::delete('/caloriesEndPoint/deleteMeal/{meal}', [\App\Http\Controllers\CaloriesAPIBotController::class, 'destroy'])->name('calculations.destroy');

});


