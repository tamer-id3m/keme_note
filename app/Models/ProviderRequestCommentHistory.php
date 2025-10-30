<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class ProviderRequestCommentHistory extends Model
{
    use HasFactory;

    protected $table = 'provider_note_comment_histories';

    protected $fillable = ['user_id', 'body', 'provider_note_comment_id', 'patient_id', 'edited_by'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function providerRequestComment(): BelongsTo
    {
        return $this->belongsTo(ProviderRequestComment::class, 'provider_note_comment_id');
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