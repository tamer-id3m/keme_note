<?php

namespace App\Http\Resources\InternalNote;

use App\Services\Timezone\TimezoneService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InternalNoteWithCommentsResource extends JsonResource
{
    public function toArray($request)
    {
        $timezoneService = new TimezoneService();

        $comments = $this->noteComments->map(function ($comment) use ($timezoneService) {
            $role = DB::table('roles')
                ->select('roles.name', 'model_has_roles.role_id')
                ->join('model_has_roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('model_has_roles.model_id', $comment->user_id)
                ->get();

            $updatedAtComment = Carbon::parse($timezoneService->convertFromUTCToUserTimezone($comment->updated_at));

            return [
                'id' => $comment->id,
                'message' => $comment->body,
                'edited' => $comment->edited,
                'user_id' => $comment->user_id,
                'patient_id' => $comment->patient_id,
                'role' => $role[0]->name,
                'user' => [
                    'full_name' => $comment->user
                    ? trim($comment->user->name . ' ' . $comment->user->last_name)
                    : null,
                    'username' => $comment->user ? $comment->user->username : null,
                    'photo' => $comment->user ? $comment->user->photo : null,
                ],
                'updated_at' => $updatedAtComment ? $updatedAtComment->format('Y-m-d H:i:s') : null,
                'created_from' => $comment->updated_at->diffForHumans(),
            ];
        });

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
            'comments' => $comments,
            'role' => $role[0]->name,
            'updated_at' => $updatedAtNote ? $updatedAtNote->format('Y-m-d H:i:s') : null,
            'created_from' => $this->updated_at->diffForHumans(),
        ];
    }
}