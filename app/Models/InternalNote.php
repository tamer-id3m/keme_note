<?php

namespace App\Models;

use Elastic\ScoutDriverPlus\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class InternalNote extends Model
{
    use HasFactory;
    use Searchable;

    // use SoftDeletes;

    protected $fillable = ['body', 'user_id', 'comment_id', 'patient_id', 'edited'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function noteComments()
    {
        return $this->hasMany(NoteComment::class, 'internal_note_id');
    }

    public function noteHistory()
    {
        return $this->hasMany(InternalNoteHistory::class, 'internal_note_id');
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

      public function searchableAs()
    {
        return env('SCOUT_PREFIX') . 'internalnote';
    }
      public function toSearchableArray()
    {
        $this->load('user','noteComments');

        $array = $this->toArray();

         return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'message' => strtolower($this->name),
            'uuid' => $this->uuid,

        ];

    }


}