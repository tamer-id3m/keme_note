<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class AiNote extends Model
{
    use HasFactory;

    // use SoftDeletes;

    protected $fillable = ['patient_id', 'doctor_id', 'approved', 'is_shared', 'note', 'approval_date', 'approved_by', 'aienv_id'];

    // Accessor for 'note' attribute
    public function getNoteAttribute($value)
    {
        // Decrypt the 'note' attribute if it's not null
        if ($value) {
            return Crypt::decryptString($value);
        }

        return null;
    }

    public function aiEnv()
    {
        return $this->belongsTo(AiEnv::class, 'aienv_id');
    }

    // Mutator for 'note' attribute
    public function setNoteAttribute($value)
    {
        // Encrypt the 'note' attribute before storing
        $this->attributes['note'] = Crypt::encryptString($value);
    }

    public function getDateAttribute($value)
    {
        return Carbon::parse($value)->format('m-d-y');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function clinicalNotes()
    {
        return $this->hasMany(ClinicalNote::class, 'note_id');
    }

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }
}