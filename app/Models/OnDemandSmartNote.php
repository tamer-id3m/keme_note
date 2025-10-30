<?php

namespace App\Models;

use App\Models\User;
use Elastic\ScoutDriverPlus\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;



class OnDemandSmartNote extends Model
{
    use HasFactory;
    use Searchable;
 
    protected $fillable = ['spoken_languages','approved', 'is_shared', 'note', 'approval_date', 'patient_id', 'doctor_id', 'approved_by', 'ai_diagnosis', 'ai_first_result'];

    public function searchableAs(): string
    {
        return env('SCOUT_PREFIX') . 'ondemandsmartnotes';
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'doctor_id'  => $this->doctor_id
        ];
    }

    public function queueLists(): HasMany
    {
        return $this->hasMany(QueueList::class, 'note_id', 'id')->where('model_name', 'OnDemandSmartNote');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}