<?php

namespace App\Models;

use App\Enums\ContextModel;
use App\Models\V4\ProviderCategory;
use Elastic\ScoutDriverPlus\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Context extends Model
{
    use HasFactory;

    use Searchable;

    protected $fillable = [
        'system_prompt',
        'model',
        'name',
        'type',
        'is_active',
        'aienv_id',
        'model_id',
        'appointment_type',
        'keme_direct',
        'provider_category_id'
    ];

    // protected $casts = [
    //     'model' => ContextModel::class,
    // ];

    public function aiEnv()
    {
        return $this->belongsTo(AiEnv::class, 'aienv_id');
    }
    public function aiModel()
    {
        return $this->belongsTo(AIModel::class, 'model_id');
    }

    public function form()
    {
        return $this->hasMany(Form::class, 'clinic_id', 'clinic_id');
        // 'forms.clinic_id' matches 'contexts.clinic_id'
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class , 'user_id' ,'id');
    }

    public function providerCategory()
    {
        return $this->belongsTo(ProviderCategory::class, 'provider_category_id');
    }

    public function searchableAs()
    {
        return env('SCOUT_PREFIX') . 'context';
    }

    public function toSearchableArray()
    {

        return [
            'id' => $this->id,
            'name' =>strtolower($this->name),
            'model' => $this->model,
            'system_prompt' => $this->system_prompt,
            'system_output' => $this->system_output,
        ];
    }

}