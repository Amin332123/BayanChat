<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReactionRequest;
use App\Http\Requests\StoreMessageRequest;
use App\Http\Requests\UpdateMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageController extends Controller
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {}

    public function index(Request $request, Conversation $conversation): JsonResource
    {
        $user = $request->user();

        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            abort(403);
        }

        $messages = $this->conversationService->getMessages(
            $conversation,
            $user,
            $request->integer('per_page', 50),
            $request->integer('before_id', null),
        );

        return MessageResource::collection($messages);
    }

    public function store(StoreMessageRequest $request, Conversation $conversation): JsonResource
    {
        $message = $this->conversationService->sendMessage(
            $conversation,
            $request->user(),
            $request->input('content'),
            $request->integer('parent_id', null),
        );

        $message->load(['sender', 'parent', 'reactions']);

        return new MessageResource($message);
    }

    public function update(UpdateMessageRequest $request, Conversation $conversation, Message $message): JsonResource
    {
        if ($message->conversation_id !== $conversation->id) {
            abort(404);
        }

        $message->update([
            'content' => $request->input('content'),
        ]);

        $message->load(['sender', 'parent', 'reactions']);

        return new MessageResource($message);
    }

    public function destroy(Request $request, Conversation $conversation, Message $message): JsonResponse
    {
        if ($message->conversation_id !== $conversation->id) {
            abort(404);
        }

        $deleted = $this->conversationService->deleteMessage($message, $request->user());

        if (!$deleted) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['message' => 'Message deleted']);
    }

    public function react(ReactionRequest $request, Conversation $conversation, Message $message): JsonResource
    {
        if ($message->conversation_id !== $conversation->id) {
            abort(404);
        }

        $this->conversationService->addReaction(
            $message,
            $request->user(),
            $request->input('reaction'),
        );

        $message->load(['reactions']);

        return new MessageResource($message);
    }

    public function unreact(ReactionRequest $request, Conversation $conversation, Message $message): JsonResource
    {
        if ($message->conversation_id !== $conversation->id) {
            abort(404);
        }

        $this->conversationService->removeReaction(
            $message,
            $request->user(),
            $request->input('reaction'),
        );

        $message->load(['reactions']);

        return new MessageResource($message);
    }
}
