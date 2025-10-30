<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProviderRequestComment extends Model
{
    use HasFactory;

    protected $table = 'provider_note_comments';

    protected $fillable = ['body', 'user_id', 'provider_note_id', 'patient_id', 'edited'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function providerRequest()
    {
        return $this->belongsTo(ProviderRequest::class, 'provider_note_id');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }
}