<?php

use App\Http\Controllers\ClinicalNoteController;
use App\Http\Controllers\Internal\ClinicalNoteController as InternalClinicalNoteController;
use Illuminate\Support\Facades\Route;

Route::prefix("internal")->middleware(['internal'])->group(function () {
    Route::prefix('clinical-notes')->group(function () {
        Route::get('/{id}', [ClinicalNoteController::class, 'showInternal']);

        Route::get('/patient/{patientId}', [ClinicalNoteController::class, 'getByPatientId']);

        Route::post('/by-ids', [ClinicalNoteController::class, 'getByIds']);

        Route::delete('/patient/{patientId}', [ClinicalNoteController::class, 'deleteByPatientId']);

        Route::get('/patient/{patientId}/count', [ClinicalNoteController::class, 'getCountByPatient']);
        Route::post('update-clinical-note', [InternalClinicalNoteController::class, 'createOrUpdateClinicalNote']);
    });
});
