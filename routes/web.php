<?php

use App\Http\Controllers\PopController;
use Illuminate\Support\Facades\Route;

// No login anywhere: a pop is reached through its secret link (/p/{slug}).
Route::controller(PopController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::post('/pops', 'store')->name('pops.store')->middleware('throttle:10,1');

    Route::get('/p/{pop}', 'show')->name('pops.show');
    Route::post('/p/{pop}/members', 'addMember')->name('members.store')->middleware('throttle:30,1');

    Route::post('/p/{pop}/expenses', 'addExpense')->name('expenses.store')->middleware('throttle:30,1');
    Route::delete('/p/{pop}/expenses/{expense}', 'deleteExpense')->name('expenses.destroy');

    Route::post('/p/{pop}/settlements', 'settle')->name('settlements.store')->middleware('throttle:30,1');
    Route::delete('/p/{pop}/settlements/{settlement}', 'unsettle')->name('settlements.destroy');
});
