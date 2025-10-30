<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class InternalNoteHistory extends Model
{
    use HasFactory;

    protected $fillable = ['body', 'user_id', 'body', 'internal_note_id', 'patient_id', 'edited_by'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function internalNote(): BelongsTo
    {
        return $this->belongsTo(InternalNote::class);
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