<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'name' => $this->name,
            'participants' => ParticipantResource::collection($this->whenLoaded('participants')),
            'latest_message' => new MessageResource($this->whenLoaded('latestMessage')),
            'message_count' => $this->whenCounted('messages'),
            'unread_count' => $this->unread_count ?? 0,
            'tone' => $this->tone ?? null,
            'dominant_emotion' => $this->dominant_emotion ?? null,
            'crisis_detected' => $this->crisis_detected ?? false,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
