<?php

namespace App\Http\Resources;

use App\Services\ConversationAnalyzer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $analyzer = app(ConversationAnalyzer::class);
        $analysis = $analyzer->analyzeMessage($this->resource);

        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender' => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
            ],
            'type' => $this->type->value,
            'content' => $this->content,
            'parent' => $this->whenLoaded('parent', fn() => new self($this->parent)),
            'reactions' => ReactionResource::collection($this->whenLoaded('reactions')),
            'analysis' => [
                'has_emotion' => $analysis['has_emotion'],
                'dominant_emotion' => $analysis['dominant_emotion'],
                'intensity' => $analysis['intensity'],
                'is_crisis' => $analysis['is_crisis'],
                'is_casual' => $analysis['is_casual'],
            ],
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
