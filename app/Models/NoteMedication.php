<?php

namespace App\Models\v3;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\HasApiTokens;

class NoteMedication extends Model
{
    use HasApiTokens;
    use HasFactory;

    protected $table = 'note_medictions';

    protected $fillable = [
        'id',
        'note_id',
        'mediction_id',
        'dosage_id',

    ];

    public function dosage()
    {
        return $this->hasMany(MedicationDosage::class, 'medication_id');
    }
}