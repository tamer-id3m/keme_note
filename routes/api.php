<?php

use App\Http\Controllers\ClinicalNoteController;
use App\Http\Controllers\InternalNoteCommentController;
use App\Http\Controllers\InternalNotecommentHistoryController;
use App\Http\Controllers\ProviderNoteController;
use App\Http\Controllers\V4\InternalNoteController;
use App\Http\Controllers\V4\InternalNoteHistoryController;
use App\Http\Controllers\V4\OnDemandSmartNoteController;
use App\Http\Controllers\V4\PatientNotesController;
use App\Http\Controllers\V4\ProviderNoteCommentController;
use App\Http\Controllers\V4\ProviderRequestCommentHistoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api'])->group(function () {
    Route::prefix('clinical-notes')->group(function () {
        Route::get('/{id}', [ClinicalNoteController::class, 'show']);
        Route::post('/', [ClinicalNoteController::class, 'store']);
        Route::put('/{id}', [ClinicalNoteController::class, 'update']);
        Route::delete('/{id}', [ClinicalNoteController::class, 'destroy']);
        Route::post('/share-status/{id}', [ClinicalNoteController::class, 'shareStatus']);
    });

     Route::prefix('note-comments')->group(function () {
        Route::get('/index/{id}', [InternalNoteCommentController::class, 'index']);
        Route::post('/create', [InternalNoteCommentController::class, 'store']);
        Route::get('/{id}', [InternalNoteCommentController::class, 'show']);
        Route::put('/edit/{note_id}/{comment_id}', [InternalNoteCommentController::class, 'update']);
        Route::delete('/{note_id}/{comment_id}', [InternalNoteCommentController::class, 'destroy']);
        Route::get('note-comments/{commentId}/history', InternalNotecommentHistoryController::class);
    });

    Route::prefix('/internal-notes')->group(function () {
    Route::get('/index/{id}', [InternalNoteController::class, 'index'])
        ->name('internal-notes.index');
    
     Route::post('/store', [InternalNoteController::class, 'store'])
        ->name('internal-notes.store');
        Route::get('/show/{id}', [InternalNoteController::class, 'show'])
        ->name('internal-notes.show');
    
     Route::put('/update/{id}', [InternalNoteController::class, 'update'])
        ->name('internal-notes.update');
        Route::delete('/delete/{id}', [InternalNoteController::class, 'destroy'])
        ->name('internal-notes.destroy');
        Route::get('/history/{internal_note_id}', [InternalNoteController::class, 'history'])
        ->name('internal-notes.history');

        Route::get('/history/{internal_note_id}', InternalNoteHistoryController::class)
        ->name('internal-notes.history');
    });
    Route::prefix('/smart-note')->group(function () {
        Route::get('/index', [OnDemandSmartNoteController::class, 'index'])
            ->name('smart-note.index');   
        Route::get('/show/{id}', [OnDemandSmartNoteController::class, 'show'])
            ->name('smart-note.index');   

        Route::post('/store', [OnDemandSmartNoteController::class, 'store'])
            ->name('smart-note.store');
    });
    Route::prefix('/patient-notes')->group(function () {
        Route::get('/', [PatientNotesController::class, 'index'])
            ->name('patient-note.index');   
    });
    Route::prefix('/provider-note-comment')->group(function () {
        Route::get('/', [ProviderNoteCommentController::class, 'index'])
            ->name('patient-note.index');
        Route::get('/{id}', [ProviderNoteCommentController::class, 'show'])
            ->name('provider-note-comment.show');
        Route::post('/', [ProviderNoteCommentController::class, 'store'])
            ->name('provider-note-comment.store');
        Route::put('/{id}', [ProviderNoteCommentController::class, 'update'])
            ->name('provider-note-comment.store');
        Route::delete('/{id}', [ProviderNoteCommentController::class, 'destroy'])
            ->name('provider-note-comment.destroy');
        Route::get('/provider-request-comments/history/{commentId}', ProviderRequestCommentHistoryController::class);
    });

    
    Route::prefix('/provider-note')->group(function () {
        Route::get('/', action: [ProviderNoteController::class, 'index'])
            ->name('provider-note.index');
        Route::post('/', [ProviderNoteController::class, 'store'])
            ->name('provider-note.store');
        Route::get('/{id}', [ProviderNoteController::class, 'show'])
            ->name('provider-note.show');
        Route::put('/{id}', [ProviderNoteController::class, 'update'])
            ->name('provider-note.update');
        Route::delete('/{id}', [ProviderNoteController::class, 'destroy'])
            ->name('provider-note.destroy');
    });
    



});