<?php

use App\Http\Controllers\DocumentController;

Route::middleware('auth')->group(function () {
    Route::resource('documents', DocumentController::class);
Route::get('documents/{document}/history', [DocumentController::class, 'history'])
     ->name('documents.history');
Route::post('documents/{document}/cursor', [DocumentController::class, 'cursor'])
     ->name('documents.cursor');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('documents/{document}/poll', [DocumentController::class, 'poll'])
     ->name('documents.poll');
});

Route::post('documents/{document}/active', [DocumentController::class, 'setActive'])
     ->name('documents.active');
Route::get('documents/{document}/active-users', [DocumentController::class, 'getActiveUsers'])
     ->name('documents.active-users');

require __DIR__.'/auth.php';

