<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;

class ConversationAnalyzer
{
    private const array EMOTION_KEYWORDS = [
        'sadness' => [
            'sad', 'sadness', 'depressed', 'depression', 'heartbroken', 'crying', 'cry',
            'hopeless', 'despair', 'miserable', 'lonely', 'loneliness', 'hurt', 'pain',
            'broken', 'devastated', 'gloomy', 'down', 'unhappy', 'sorrow', 'grief',
            'grieving', 'mourning', 'loss', 'bereavement', 'miss', 'missed',
            'حزين', 'حزن', 'اكتئاب', 'بكاء', 'يبكي', 'حطم', 'ألم', 'وحيد', 'وحدة',
            'حسرة', 'أسى', 'انهيار', 'ضعيف', 'تعب', 'تعبت',
        ],
        'anxiety' => [
            'anxious', 'anxiety', 'worried', 'worry', 'fear', 'scared', 'afraid',
            'panic', 'nervous', 'stress', 'stressed', 'overwhelmed', 'tense',
            'restless', 'uneasy', 'terrified', 'dread', 'trembling',
            'قلق', 'قلقة', 'خائف', 'خوف', 'توتر', 'ذعر', 'عصبي', 'خائفة',
            'رهبة', 'خوف شديد',
        ],
        'anger' => [
            'angry', 'anger', 'furious', 'frustrated', 'frustration', 'irritated',
            'rage', 'resentful', 'annoyed', 'mad', 'fuming', 'hostile', 'bitter',
            'غاضب', 'غضب', 'غضبان', 'محبط', 'إحباط', 'حانق', 'ثائر',
            'ساخط', 'ضيق', 'غيظ',
        ],
        'gratitude' => [
            'grateful', 'gratitude', 'thankful', 'blessed', 'appreciative',
            'شاكر', 'شاكرة', 'شكر', 'امتنان', 'ممتن', 'شكور', 'حامد',
            'الحمد لله', 'الشكر لله', 'بارك', 'نعمة',
        ],
        'happiness' => [
            'happy', 'happiness', 'joy', 'joyful', 'excited', 'excitement',
            'delighted', 'elated', 'thrilled', 'content', 'cheerful', 'glad',
            'سعيد', 'سعادة', 'فرح', 'فرحة', 'مبتهج', 'مسرور', 'بهجة',
            'مرح', 'ابتهاج', 'سرور',
        ],
        'hope' => [
            'hope', 'hopeful', 'optimistic', 'trusting', 'faith', 'positive',
            'أمل', 'متفائل', 'تفاؤل', 'أمل', 'رتجاء', 'ثقة', 'توكل',
        ],
        'guilt' => [
            'guilt', 'guilty', 'regret', 'regretful', 'remorse', 'ashamed',
            'shame', 'sin', 'sinned', 'wrong', 'forgive', 'forgiveness',
            'repent', 'repentance', 'sorry',
            'ذنب', 'ذنوب', 'خطيئة', 'ندم', 'توبة', 'استغفر', 'مذنب',
            'خجل', 'خجلان', 'أسف', 'تائب',
        ],
        'yearning' => [
            'marry', 'marriage', 'spouse', 'husband', 'wife', 'love',
            'relationship', 'partner', 'children', 'baby', 'family',
            'longing', 'yearning', 'desire', 'wish', 'alone',
            'زواج', 'زوج', 'زوجة', 'أطفال', 'حب', 'شريك', 'حنان',
            'عائلة', 'فرصة', 'عزباء', 'عازب', 'وحيد',
        ],
        'financial_worry' => [
            'money', 'debt', 'broke', 'poor', 'poverty', 'financial',
            'bills', 'income', 'salary', 'provision', 'rizq', 'struggling',
            'رزق', 'مال', 'فلوس', 'ديون', 'فقير', 'فقر', 'محتاج',
            'مرتب', 'راتب', 'حساب', 'مصاريف',
        ],
        'contentment' => [
            'peace', 'peaceful', 'calm', 'tranquility', 'satisfied',
            'content', 'contentment', 'qalbi mutma\'inn', 'طمأنينة',
            'سلام', 'هدوء', 'رضا', 'راحة', 'سكينة', 'مطمئن', 'قلبي مطمئن',
        ],
    ];

    private const array CRISIS_INDICATORS = [
        'kill myself', 'kill me', 'suicide', 'end my life', 'end my own life',
        'want to die', 'better off dead', 'harm myself', 'self-harm',
        'self harm', 'hurt myself', 'take my own life', 'لا اريد الحياة',
        'انتحار', 'أقتل نفسي', 'أذي نفسي', 'موت', 'أتمنى الموت',
        'ما عاد عندي أمل', 'خلصت حياتي', 'ماباقيش عايش',
    ];

    public function analyzeMessage(Message $message): array
    {
        $content = $message->content;
        $detectedEmotions = [];
        $intensity = 0;

        foreach (self::EMOTION_KEYWORDS as $emotion => $keywords) {
            $matches = 0;
            foreach ($keywords as $keyword) {
                if (mb_stripos($content, $keyword) !== false) {
                    $matches++;
                }
            }
            if ($matches > 0) {
                $detectedEmotions[$emotion] = $matches;
                $intensity += $matches;
            }
        }

        $isCrisis = false;
        foreach (self::CRISIS_INDICATORS as $indicator) {
            if (mb_stripos($content, $indicator) !== false) {
                $isCrisis = true;
                break;
            }
        }

        $dominantEmotion = !empty($detectedEmotions)
            ? array_keys($detectedEmotions, max($detectedEmotions))[0]
            : null;

        return [
            'has_emotion' => !empty($detectedEmotions),
            'dominant_emotion' => $dominantEmotion,
            'emotions' => $detectedEmotions,
            'intensity' => $intensity,
            'is_crisis' => $isCrisis,
            'is_casual' => empty($detectedEmotions) && !$isCrisis,
        ];
    }

    public function analyzeConversation(Conversation $conversation): array
    {
        $messages = $conversation->messages()
            ->where('type', 'text')
            ->orderBy('created_at')
            ->get();

        $emotionFrequency = [];
        $totalIntensity = 0;
        $messageCount = $messages->count();
        $emotionalMessageCount = 0;
        $crisisDetected = false;
        $toneHistory = [];

        foreach ($messages as $message) {
            $analysis = $this->analyzeMessage($message);
            $toneHistory[] = [
                'message_id' => $message->id,
                'sender_id' => $message->sender_id,
                'analysis' => $analysis,
            ];

            if ($analysis['has_emotion'] || $analysis['is_crisis']) {
                $emotionalMessageCount++;
            }

            if ($analysis['is_crisis']) {
                $crisisDetected = true;
            }

            $totalIntensity += $analysis['intensity'];

            foreach ($analysis['emotions'] as $emotion => $count) {
                $emotionFrequency[$emotion] = ($emotionFrequency[$emotion] ?? 0) + $count;
            }
        }

        $dominantEmotion = !empty($emotionFrequency)
            ? array_keys($emotionFrequency, max($emotionFrequency))[0]
            : null;

        $emotionalRatio = $messageCount > 0
            ? $emotionalMessageCount / $messageCount
            : 0;

        $tone = 'casual';
        if ($crisisDetected) {
            $tone = 'crisis';
        } elseif ($emotionalRatio > 0.6) {
            $tone = 'deeply_emotional';
        } elseif ($emotionalRatio > 0.3) {
            $tone = 'emotional';
        } elseif ($emotionalRatio > 0.1) {
            $tone = 'lightly_emotional';
        }

        return [
            'tone' => $tone,
            'dominant_emotion' => $dominantEmotion,
            'emotion_frequency' => $emotionFrequency,
            'emotional_ratio' => round($emotionalRatio, 2),
            'total_messages' => $messageCount,
            'emotional_messages' => $emotionalMessageCount,
            'average_intensity' => $messageCount > 0
                ? round($totalIntensity / $messageCount, 2)
                : 0,
            'crisis_detected' => $crisisDetected,
            'is_supportive' => $tone === 'crisis' || $tone === 'deeply_emotional',
            'tone_history' => $toneHistory,
        ];
    }

    public function classifyInput(string $input): array
    {
        $analysis = $this->analyzeString($input);

        if ($analysis['is_crisis']) {
            return [
                'category' => 'crisis',
                'intent' => 'seeking_help',
                'requires_immediate_attention' => true,
            ];
        }

        if ($analysis['is_casual']) {
            return [
                'category' => 'conversation',
                'intent' => 'casual',
                'requires_immediate_attention' => false,
            ];
        }

        return [
            'category' => $analysis['dominant_emotion'] ?? 'general',
            'intent' => 'emotional_sharing',
            'requires_immediate_attention' => false,
        ];
    }

    private function analyzeString(string $input): array
    {
        $detectedEmotions = [];
        $intensity = 0;

        foreach (self::EMOTION_KEYWORDS as $emotion => $keywords) {
            $matches = 0;
            foreach ($keywords as $keyword) {
                if (mb_stripos($input, $keyword) !== false) {
                    $matches++;
                }
            }
            if ($matches > 0) {
                $detectedEmotions[$emotion] = $matches;
                $intensity += $matches;
            }
        }

        $isCrisis = false;
        foreach (self::CRISIS_INDICATORS as $indicator) {
            if (mb_stripos($input, $indicator) !== false) {
                $isCrisis = true;
                break;
            }
        }

        return [
            'has_emotion' => !empty($detectedEmotions),
            'dominant_emotion' => !empty($detectedEmotions)
                ? array_keys($detectedEmotions, max($detectedEmotions))[0]
                : null,
            'emotions' => $detectedEmotions,
            'intensity' => $intensity,
            'is_crisis' => $isCrisis,
            'is_casual' => empty($detectedEmotions) && !$isCrisis,
        ];
    }
}
