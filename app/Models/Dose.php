<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dose extends Model
{
    use HasFactory;

    // use SoftDeletes;

    protected $fillable = ['name', 'directions', 'medication_id', 'active'];

    public function medication()
    {
        return $this->belongsTo(Medication::class, 'medication_id');
    }
}