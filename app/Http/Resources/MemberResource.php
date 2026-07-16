<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'part1_id' => $this->part1_id,
            'surname' => $this->surname,
            'first_name' => $this->first_name,
            'midle_name' => $this->midle_name,
            'place_of_birth' => $this->place_of_birth,
            'date_of_birth' => $this->date_of_birth,
            'age' => $this->age,
            'sex_at_birth' => $this->sex_at_birth,
            'cellular_no' => $this->cellular_no,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
