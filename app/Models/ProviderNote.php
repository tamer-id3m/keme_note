<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ProviderNote extends Model
{
    use HasFactory;

    protected $fillable = ['body', 'user_id', 'doctor_id', 'patient_id', 'edited', 'assignees'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function comments()
    {
        return $this->hasMany(ProviderNoteComment::class);
    }

    // Boot method to add cascading delete
    protected static function boot()
    {
        parent::boot();

        // Delete related comments when a ProviderNote is deleted
        static::deleting(function ($providerNote) {
            $providerNote->comments()->delete();
        });
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
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