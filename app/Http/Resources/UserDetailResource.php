<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'user_id' => $this->user_id,
            'id_karyawan' => $this->id_karyawan,
            'nik' => $this->nik,
            'name' => $this->name,
            'department' => $this->department,
            'sub_department' => $this->sub_department,
            'position' => $this->position,
            'direct_supervisor_id' => $this->direct_supervisor_id,
            'direct_supervisor_position' => $this->direct_supervisor_position,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
