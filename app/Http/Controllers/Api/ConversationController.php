<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConversationRequest;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Models\User;
use App\Services\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationController extends Controller
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {}

    public function index(Request $request): JsonResource
    {
        $conversations = $this->conversationService->getUserConversations($request->user());

        return ConversationResource::collection($conversations);
    }

    public function store(StoreConversationRequest $request): JsonResource
    {
        $user = $request->user();
        $type = $request->input('type');

        if ($type === 'private') {
            $otherId = $request->input('participant_ids')[0];
            $other = User::findOrFail($otherId);
            $conversation = $this->conversationService->createPrivateConversation($user, $other);
        } else {
            $conversation = $this->conversationService->createGroupConversation(
                $user,
                $request->input('participant_ids'),
                $request->input('name'),
            );
        }

        $conversation->load(['participants', 'latestMessage.sender']);

        return new ConversationResource($conversation);
    }

    public function show(Request $request, Conversation $conversation): JsonResource
    {
        $user = $request->user();

        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            abort(403, 'Not a participant of this conversation');
        }

        $conversation->load(['participants', 'latestMessage.sender']);

        $analysis = $this->conversationService->analyzeConversationTone($conversation);
        $conversation->tone = $analysis['tone'];
        $conversation->dominant_emotion = $analysis['dominant_emotion'];
        $conversation->crisis_detected = $analysis['crisis_detected'];

        return new ConversationResource($conversation);
    }

    public function destroy(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            abort(403);
        }

        $conversation->participants()->detach($user->id);

        if ($conversation->participants()->count() === 0) {
            $conversation->delete();
        }

        return response()->json(['message' => 'Removed from conversation']);
    }

    public function markAsRead(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            abort(403);
        }

        $this->conversationService->markAsRead($conversation, $user);

        return response()->json(['message' => 'Marked as read']);
    }

    public function analyze(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            abort(403);
        }

        $analysis = $this->conversationService->analyzeConversationTone($conversation);

        return response()->json([
            'data' => $analysis,
        ]);
    }

    public function classifyMessage(Request $request): JsonResponse
    {
        $request->validate(['input' => 'required|string|min:1|max:5000']);

        $classification = $this->conversationService->classifyMessageInput($request->input('input'));

        return response()->json([
            'data' => $classification,
        ]);
    }
}
