<?php

namespace App\Models;

use App\Models\NoteDoasge;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use App\Models\v3\AppointmentSummary;
use App\Traits\HasRoleBasedFilter;

class ClinicalNote extends Model
{
    use HasFactory;
    use HasRoleBasedFilter;
    // use SoftDeletes;

    protected $fillable = [
        'subjective',
        'chief_complaint',
        'history_of_present_illness',
        'current_medications',
        'diagnosis',
        'assessments',
        'plan',
        'procedures',
        'medications',
        'risks_benefits_discussion',
        'care_plan',
        'next_follow_up',
        'next_follow_up_value',
        'next_follow_up_timeframe',
        'date',
        'patient_id',
        'doctor_id',
        'note_id',
        'is_shared',
        'resource',
        'on_demand_smart_note_id',
        "appointment_summary_id"
    ];

    public function aiNote()
    {
        return $this->belongsTo(AiNote::class, 'note_id');
    }
    public function onDemandSmartNote()
    {
        return $this->belongsTo(OnDemandSmartNote::class, 'on_demand_smart_note_id');
    }

    public function appointmentSummary()
    {
        return $this->belongsTo(AppointmentSummary::class, 'appointment_summary_id');
    }

    /**
     * Mutator to encrypt the attribute value before saving.
     */
    protected function setAttributeEncrypted($value, $attribute)
    {
        $this->attributes[$attribute] = Crypt::encryptString($value);
    }

    /**
     * Accessor to decrypt the attribute value when retrieving.
     *
     * @return string
     */
    protected function getAttributeDecrypted($value, $attribute)
    {
        return Crypt::decryptString($value);
    }

    // Mutators and accessors for each fillable attribute

    public function setSubjectiveAttribute($value)
    {
        $this->setAttributeEncrypted($value, 'subjective');
    }

    public function getSubjectiveAttribute($value)
    {
        return $this->getAttributeDecrypted($value, 'subjective');
    }

    public function setChiefComplaintAttribute($value)
    {
        $this->setAttributeEncrypted($value, 'chief_complaint');
    }

    public function getChiefComplaintAttribute($value)
    {
        return $this->getAttributeDecrypted($value, 'chief_complaint');
    }

    public function setHistoryOfPresentIllnessAttribute($value)
    {
        $this->setAttributeEncrypted($value, 'history_of_present_illness');
    }

    public function getHistoryOfPresentIllnessAttribute($value)
    {
        return $this->getAttributeDecrypted($value, 'history_of_present_illness');
    }

    public function setCurrentMedicationsAttribute($value)
    {
        $this->setAttributeEncrypted($value, 'current_medications');
    }

    public function getCurrentMedicationsAttribute($value)
    {
        return $this->getAttributeDecrypted($value, 'current_medications');
    }

    public function setDiagnosisAttribute($value)
    {
        $this->setAttributeEncrypted($value, 'diagnosis');
    }

    public function getDiagnosisAttribute($value)
    {
        return $this->getAttributeDecrypted($value, 'diagnosis');
    }

    public function setAssessmentsAttribute($value)
    {
        $this->setAttributeEncrypted($value, 'assessments');
    }

    public function getAssessmentsAttribute($value)
    {
        return $this->getAttributeDecrypted($value, 'assessments');
    }

    public function setPlanAttribute($value)
    {
        $this->setAttributeEncrypted($value, 'plan');
    }

    public function getPlanAttribute($value)
    {
        return $this->getAttributeDecrypted($value, 'plan');
    }

    public function setProceduresAttribute($value)
    {
        $this->setAttributeEncrypted($value, 'procedures');
    }

    public function getProceduresAttribute($value)
    {
        return $this->getAttributeDecrypted($value, 'procedures');
    }

    public function setMedicationsAttribute($value)
    {
        $this->setAttributeEncrypted($value, 'medications');
    }

    public function getMedicationsAttribute($value)
    {
        return $this->getAttributeDecrypted($value, 'medications');
    }

    public function setRisksBenefitsDiscussionAttribute($value)
    {
        $this->setAttributeEncrypted($value, 'risks_benefits_discussion');
    }

    public function getRisksBenefitsDiscussionAttribute($value)
    {
        return $this->getAttributeDecrypted($value, 'risks_benefits_discussion');
    }

    public function setCarePlanAttribute($value)
    {
        $this->setAttributeEncrypted($value, 'care_plan');
    }

    public function getCarePlanAttribute($value)
    {
        return $this->getAttributeDecrypted($value, 'care_plan');
    }

    public function setNextFollowUpAttribute($value)
    {
        $this->setAttributeEncrypted($value, 'next_follow_up');
    }

    public function getNextFollowUpAttribute($value)
    {
        return $this->getAttributeDecrypted($value, 'next_follow_up');
    }

    public function doasge()
    {
        return $this->hasMany(NoteDoasge::class, 'note_id');
    }

    public function patients()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    /**
     * The labs that belong to the note.
     */
    public function labs()
    {
        return $this->belongsToMany(Lab::class, 'note_labs', 'note_id', 'lab_id');
    }

    public function medications()
    {
        return $this->belongsToMany(Medication::class, 'note_medictions', 'note_id', 'mediction_id');
    }

    public function getDateAttribute($value)
    {
        return Carbon::parse($value)->format('m-d-y');
    }
}