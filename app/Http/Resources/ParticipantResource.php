<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParticipantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'last_read_at' => $this->pivot->last_read_at?->toISOString(),
            'joined_at' => $this->pivot->joined_at->toISOString(),
        ];
    }
}
