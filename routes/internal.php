<?php

use App\Http\Controllers\ClinicalNoteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['internal'])->group(function () {
    Route::prefix('clinical-notes')->group(function () {
        Route::get('/{id}', [ClinicalNoteController::class, 'showInternal']);
        
        Route::get('/patient/{patientId}', [ClinicalNoteController::class, 'getByPatientId']);
        
        Route::post('/by-ids', [ClinicalNoteController::class, 'getByIds']);
        
        Route::delete('/patient/{patientId}', [ClinicalNoteController::class, 'deleteByPatientId']);
        
        Route::get('/patient/{patientId}/count', [ClinicalNoteController::class, 'getCountByPatient']);
    });
});