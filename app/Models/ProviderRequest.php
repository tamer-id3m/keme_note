<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class ProviderRequest extends Model
{
    use HasFactory;

    // use SoftDeletes;

    protected $table = 'provider_notes';

    protected $fillable = ['body', 'user_id', 'patient_id', 'doctor_id', 'edited', 'assignees'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function providerRequestComments()
    {
        return $this->hasMany(ProviderRequestComment::class, 'provider_note_id');
    }

    // Boot method to add cascading delete
    protected static function boot()
    {
        parent::boot();

        // Delete related comments when a ProviderNote is deleted
        static::deleting(function ($providerNote) {
            $providerNote->providerRequestComments()->delete();
        });

        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
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