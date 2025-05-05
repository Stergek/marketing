<?php

use App\Http\Controllers\CustomPageController;

// Route::get('/admin/test-custom', [CustomPageController::class, 'index'])->name('test-custom');

Route::get('/test-tailwind', function () {
    return view('test-tailwind');
});