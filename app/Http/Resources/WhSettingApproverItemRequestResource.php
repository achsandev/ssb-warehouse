<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WhSettingApproverItemRequestResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'uid' => $this->uid,
            'requester_role_id' => $this->requester_role_id,
            'requester_role_name' => $this->requester_role_name,
            'approver_role_id' => $this->approver_role_id,
            'approver_role_name' => $this->approver_role_name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
