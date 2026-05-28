<?php

namespace App\Services;

use App\Enums\ConversationType;
use App\Enums\MessageType;
use App\Events\ConversationUpdated;
use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ConversationService
{
    public function __construct(
        private readonly ConversationAnalyzer $analyzer,
    ) {}

    public function createPrivateConversation(User $initiator, User $other): Conversation
    {
        $existing = $this->findExistingPrivateConversation($initiator, $other);

        if ($existing) {
            return $existing;
        }

        $conversation = Conversation::create([
            'type' => ConversationType::Private,
        ]);

        $conversation->participants()->attach([
            $initiator->id,
            $other->id,
        ]);

        $conversation->messages()->create([
            'sender_id' => $initiator->id,
            'type' => MessageType::System,
            'content' => "Conversation started between {$initiator->name} and {$other->name}",
        ]);

        return $conversation;
    }

    public function createGroupConversation(User $creator, array $participantIds, string $name = null): Conversation
    {
        $participantIds = array_unique([$creator->id, ...$participantIds]);

        $conversation = Conversation::create([
            'type' => ConversationType::Group,
            'name' => $name,
        ]);

        $conversation->participants()->attach($participantIds);

        $conversation->messages()->create([
            'sender_id' => $creator->id,
            'type' => MessageType::System,
            'content' => "{$creator->name} created the group" . ($name ? " \"{$name}\"" : ''),
        ]);

        return $conversation;
    }

    public function sendMessage(Conversation $conversation, User $sender, string $content, int $parentId = null): Message
    {
        $message = $conversation->messages()->create([
            'sender_id' => $sender->id,
            'type' => MessageType::Text,
            'content' => $content,
            'parent_id' => $parentId,
        ]);

        $message->load(['sender', 'parent', 'reactions']);

        broadcast(new MessageSent($message))->toOthers();

        return $message;
    }

    public function deleteMessage(Message $message, User $user): bool
    {
        if ($message->sender_id !== $user->id) {
            return false;
        }

        $message->delete();

        broadcast(new ConversationUpdated($message->conversation))->toOthers();

        return true;
    }

    public function markAsRead(Conversation $conversation, User $user): void
    {
        $conversation->participants()->updateExistingPivot($user->id, [
            'last_read_at' => now(),
        ]);

        broadcast(new MessageRead($conversation, $user))->toOthers();
    }

    public function addReaction(Message $message, User $user, string $reaction): MessageReaction
    {
        return MessageReaction::firstOrCreate([
            'message_id' => $message->id,
            'user_id' => $user->id,
            'reaction' => $reaction,
        ]);
    }

    public function removeReaction(Message $message, User $user, string $reaction): bool
    {
        return (bool) MessageReaction::where([
            'message_id' => $message->id,
            'user_id' => $user->id,
            'reaction' => $reaction,
        ])->delete();
    }

    public function getMessages(Conversation $conversation, User $user, int $perPage = 50, int $beforeId = null)
    {
        $query = $conversation->messages()
            ->with(['sender', 'parent', 'reactions.user'])
            ->orderByDesc('created_at');

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        return $query->paginate($perPage);
    }

    public function getUserConversations(User $user): Collection
    {
        return $user->conversations()
            ->with(['participants', 'latestMessage.sender'])
            ->withCount('messages')
            ->get()
            ->each(function ($conversation) use ($user) {
                $pivot = $conversation->participants->find($user->id)?->pivot;
                $conversation->unread_count = $pivot
                    ? $conversation->messages()
                        ->where('created_at', '>', $pivot->last_read_at ?? now()->subYear())
                        ->where('sender_id', '!=', $user->id)
                        ->count()
                    : 0;

                $analysis = $this->analyzer->analyzeConversation($conversation);
                $conversation->tone = $analysis['tone'];
                $conversation->dominant_emotion = $analysis['dominant_emotion'];
                $conversation->crisis_detected = $analysis['crisis_detected'];
            });
    }

    public function analyzeConversationTone(Conversation $conversation): array
    {
        return $this->analyzer->analyzeConversation($conversation);
    }

    public function classifyMessageInput(string $input): array
    {
        return $this->analyzer->classifyInput($input);
    }

    private function findExistingPrivateConversation(User $user1, User $user2): ?Conversation
    {
        $conversations = Conversation::where('type', ConversationType::Private)
            ->whereHas('participants', fn(Builder $q) => $q->where('user_id', $user1->id))
            ->whereHas('participants', fn(Builder $q) => $q->where('user_id', $user2->id))
            ->withCount('participants')
            ->having('participants_count', 2)
            ->first();

        return $conversations;
    }
}
