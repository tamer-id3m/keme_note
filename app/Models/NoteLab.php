<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\HasApiTokens;

class NoteLab extends Model
{
    use HasApiTokens;
    use HasFactory;

    protected $fillable = [
        'id',
        'note_id',
        'lab_id',

    ];
}