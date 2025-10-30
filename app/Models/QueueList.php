<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class QueueList extends Model
{
    use HasFactory;
    
    public function onDemandSmartNote(): BelongsTo
    {
        return $this->belongsTo(OnDemandSmartNote::class, 'note_id' );
    }
    public function publicAppointmentSummary(): BelongsTo
    {
        return $this->belongsTo(PublicAppointmentSummary::class, 'note_id' );
    }
    public function patientSubmission(): BelongsTo
    {
        return $this->belongsTo(PatientSubmission::class, 'note_id' );
    }
}