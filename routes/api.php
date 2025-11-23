<?php

use App\Http\Controllers\ClinicalNoteController;
use App\Http\Controllers\InternalNoteCommentController;
use App\Http\Controllers\InternalNotecommentHistoryController;
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

});