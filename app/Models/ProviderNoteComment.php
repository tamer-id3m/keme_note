<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ProviderNoteComment extends Model
{
    use HasFactory;

    protected $fillable = ['body', 'user_id', 'provider_note_id', 'patient_id', 'edited'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function providerNote()
    {
        return $this->belongsTo(ProviderNote::class);
    }

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