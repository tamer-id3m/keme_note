<?php

use App\Http\Clients\UserClient;
use App\Http\Controllers\ClinicalNoteController;
use App\Http\Controllers\Internal\ClinicalNoteController as InternalClinicalNoteController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Internal\QueueListController;

Route::prefix("internal")->middleware(['internal'])->group(function () {
    Route::prefix('clinical-notes')->group(function () {
        Route::get('/{id}', [ClinicalNoteController::class, 'showInternal']);

        Route::get('/patient/{patientId}', [ClinicalNoteController::class, 'getByPatientId']);

        Route::post('/by-ids', [ClinicalNoteController::class, 'getByIds']);

        Route::delete('/patient/{patientId}', [ClinicalNoteController::class, 'deleteByPatientId']);

        Route::get('/patient/{patientId}/count', [ClinicalNoteController::class, 'getCountByPatient']);
        Route::post('update-clinical-note', [InternalClinicalNoteController::class, 'createOrUpdateClinicalNote']);
        Route::post('/users/by-role', [UserClient::class, 'getUsersByRole']);
    });
    Route::post('queue-list', [QueueListController::class, 'queueLists']);
    Route::post('not-in-progress-queue-list', [QueueListController::class, 'getNotProgressQueueList']);
    Route::delete('queue-list', [QueueListController::class, 'deleteQueueList']);
    Route::post('create-queue', [QueueListController::class, 'addQueue']);
    Route::post('edit-queue-status', [QueueListController::class, 'editQueueStatus']);
    Route::post('delete-create-queue', [QueueListController::class, 'deleteCreateQueue']);
    Route::get('/note-queue-list-bulk', [QueueListController::class, 'bulkQueueListTypes']);
    Route::delete('/note-queue-list-delete/{id}/{model}', [QueueListController::class, 'queueSyncDelete']);
    Route::post('/note-queue-sync', [QueueListController::class, 'queueSync']);

});
