<?php

namespace App\Models;

use App\Models\NoteComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class CommentHistory extends Model
{
    use HasFactory;

    protected $fillable = ['body', 'user_id', 'body', 'comment_id', 'patient_id', 'edited_by'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function noteComment(): BelongsTo
    {
        return $this->belongsTo(NoteComment::class);
    }

    // Encrypt the body attribute before saving to the database
    public function setBodyAttribute($value)
    {
        $this->attributes['body'] = Crypt::encryptString($value);
    }

    // Decrypt the body attribute when retrieving from the database
    public function getBodyAttribute($value)
    {
        return Crypt::decryptString($value);
    }
}