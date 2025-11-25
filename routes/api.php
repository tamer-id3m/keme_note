<?php

use App\Http\Controllers\ClinicalNoteController;
use App\Http\Controllers\InternalNoteCommentController;
use App\Http\Controllers\InternalNotecommentHistoryController;
use App\Http\Controllers\V4\InternalNoteController;
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
    });

});