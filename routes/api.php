<?php

use App\Http\Controllers\ClinicalNoteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api'])->group(function () {
    Route::prefix('clinical-notes')->group(function () {
        Route::get('/{id}', [ClinicalNoteController::class, 'show']);
        Route::post('/', [ClinicalNoteController::class, 'store']);
        Route::put('/{id}', [ClinicalNoteController::class, 'update']);
        Route::delete('/{id}', [ClinicalNoteController::class, 'destroy']);
        Route::post('/share-status/{id}', [ClinicalNoteController::class, 'shareStatus']);
    });
});