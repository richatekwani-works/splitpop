<?php

use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PopController;
use App\Http\Controllers\SettlementController;
use Illuminate\Support\Facades\Route;

// No login anywhere: a pop is reached through its secret link (/p/{slug}).
Route::get('/', [PopController::class, 'home'])->name('home');
Route::post('/pops', [PopController::class, 'store'])->name('pops.store')->middleware('throttle:10,1');

Route::get('/p/{pop}', [PopController::class, 'show'])->name('pops.show');
Route::post('/p/{pop}/members', [MemberController::class, 'store'])->name('members.store')->middleware('throttle:30,1');

Route::post('/p/{pop}/expenses', [ExpenseController::class, 'store'])->name('expenses.store')->middleware('throttle:30,1');
Route::delete('/p/{pop}/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

Route::post('/p/{pop}/settlements', [SettlementController::class, 'store'])->name('settlements.store')->middleware('throttle:30,1');
Route::delete('/p/{pop}/settlements/{settlement}', [SettlementController::class, 'destroy'])->name('settlements.destroy');
