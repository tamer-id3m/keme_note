<?php

namespace App\Models;

use App\Models\InternalNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NoteComment extends Model
{
    use HasFactory;

    protected $fillable = ['body', 'user_id', 'internal_note_id', 'patient_id', 'edited'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function internalNote()
    {
        return $this->belongsTo(InternalNote::class, 'comment_id');
    }

    public function commentHistories(): HasMany
    {
        return $this->hasMany(CommentHistory::class);
    }
}