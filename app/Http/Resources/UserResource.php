<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    // public function toArray($request)
    // {
    //     return [
    //         'name' => $this->name,
    //         'user_id' => $this->user_id,
    //         'settings' => $this->settings,
    //     ];
    // }

    public function toArray($request)
    {
        // Get settings from session
        $settings = session('sessionData.settings', []);

        return [
            'name' => $this->name,
            'user_id' => $this->user_id,
            'reg_id' => $this->reg_id,
            'role_id' => $this->role_id,
            'settings' => $settings,
        ];
    }
}


