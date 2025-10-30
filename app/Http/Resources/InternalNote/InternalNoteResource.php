<?php

namespace App\Http\Resources\V4\InternalNote;

use App\Services\Timezone\TimezoneService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InternalNoteResource extends JsonResource
{
    public function toArray($request)
    {
        $timezoneService = new TimezoneService();

        $role = DB::table('roles')
            ->select('roles.name', 'model_has_roles.role_id')
            ->join('model_has_roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $this->user_id)
            ->get();

        $updatedAtNote = Carbon::parse($timezoneService->convertFromUTCToUserTimezone($this->updated_at));

        return [
            'id' => $this->id,
            'message' => $this->body,
            'user_id' => $this->user_id,
           'full_name' => $this->user->name . ' ' . $this->user->last_name  ,
            'username' => $this->user->username,
            'photo' => $this->user->photo,
            'patient_id' => $this->patient_id,
            'edited' => $this->edited,
            'role' => $role[0]->name,
            'updated_at' => $updatedAtNote ? $updatedAtNote->format('Y-m-d H:i:s') : null,
            'created_from' => $this->updated_at->diffForHumans(),
        ];
    }
}