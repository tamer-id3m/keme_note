<?php

namespace App\Http\Resources\Medication;

use Illuminate\Http\Resources\Json\JsonResource;

class MedicationDoasageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->medication->name . ' => ' . $this->name,
            'directions' => $this->directions,
        ];
    }
}