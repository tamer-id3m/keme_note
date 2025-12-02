<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class ProviderRequestHistory extends Model
{
    use HasFactory;

    protected $table = 'provider_note_histories';

    protected $fillable = ['user_id', 'body', 'provider_note_id', 'doctor_id', 'edited_by'];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'user_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function providerRequest(): BelongsTo
    {
        return $this->belongsTo(ProviderRequest::class, 'provider_note_id');
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

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }
}