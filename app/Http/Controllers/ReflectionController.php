<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ReflectionController extends Controller
{
    private const OPENAI_ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    private const ISLAMIC_APP_VERSES = 'https://api.islamic.app/v1/verses/by_key/%d:%d';
    private const ISLAMIC_APP_SEARCH = 'https://api.islamic.app/v1/search';

    private array $fallbackVerseMap = [
        'loneliness' => [
            ['surah' => 2, 'ayah' => 186],
            ['surah' => 9, 'ayah' => 40],
            ['surah' => 50, 'ayah' => 16],
            ['surah' => 20, 'ayah' => 46],
            ['surah' => 28, 'ayah' => 7],
        ],
        'anxiety' => [
            ['surah' => 13, 'ayah' => 28],
            ['surah' => 65, 'ayah' => 2],
            ['surah' => 65, 'ayah' => 3],
            ['surah' => 94, 'ayah' => 5],
            ['surah' => 94, 'ayah' => 6],
            ['surah' => 29, 'ayah' => 69],
            ['surah' => 16, 'ayah' => 128],
            ['surah' => 8, 'ayah' => 2],
        ],
        'sadness' => [
            ['surah' => 2, 'ayah' => 286],
            ['surah' => 39, 'ayah' => 53],
            ['surah' => 12, 'ayah' => 87],
            ['surah' => 94, 'ayah' => 5],
            ['surah' => 94, 'ayah' => 6],
            ['surah' => 4, 'ayah' => 19],
            ['surah' => 16, 'ayah' => 97],
            ['surah' => 3, 'ayah' => 139],
        ],
        'happiness' => [
            ['surah' => 10, 'ayah' => 58],
            ['surah' => 27, 'ayah' => 19],
            ['surah' => 30, 'ayah' => 4],
            ['surah' => 3, 'ayah' => 139],
            ['surah' => 15, 'ayah' => 98],
        ],
        'gratitude' => [
            ['surah' => 14, 'ayah' => 7],
            ['surah' => 55, 'ayah' => 13],
            ['surah' => 16, 'ayah' => 18],
            ['surah' => 2, 'ayah' => 152],
            ['surah' => 31, 'ayah' => 12],
        ],
        'hope' => [
            ['surah' => 39, 'ayah' => 53],
            ['surah' => 65, 'ayah' => 2],
            ['surah' => 65, 'ayah' => 3],
            ['surah' => 2, 'ayah' => 186],
            ['surah' => 29, 'ayah' => 69],
            ['surah' => 94, 'ayah' => 5],
        ],
        'contentment' => [
            ['surah' => 13, 'ayah' => 28],
            ['surah' => 89, 'ayah' => 27],
            ['surah' => 89, 'ayah' => 28],
            ['surah' => 48, 'ayah' => 4],
            ['surah' => 16, 'ayah' => 97],
        ],
        'financial_worry' => [
            ['surah' => 65, 'ayah' => 2],
            ['surah' => 65, 'ayah' => 3],
            ['surah' => 11, 'ayah' => 6],
            ['surah' => 51, 'ayah' => 58],
            ['surah' => 2, 'ayah' => 261],
            ['surah' => 67, 'ayah' => 15],
        ],
        'grief' => [
            ['surah' => 2, 'ayah' => 156],
            ['surah' => 2, 'ayah' => 157],
            ['surah' => 2, 'ayah' => 286],
            ['surah' => 16, 'ayah' => 127],
            ['surah' => 94, 'ayah' => 5],
            ['surah' => 94, 'ayah' => 6],
        ],
        'guilt' => [
            ['surah' => 39, 'ayah' => 53],
            ['surah' => 42, 'ayah' => 25],
            ['surah' => 66, 'ayah' => 8],
            ['surah' => 25, 'ayah' => 70],
            ['surah' => 9, 'ayah' => 104],
        ],
        'anger' => [
            ['surah' => 3, 'ayah' => 134],
            ['surah' => 42, 'ayah' => 37],
            ['surah' => 42, 'ayah' => 40],
            ['surah' => 25, 'ayah' => 63],
            ['surah' => 7, 'ayah' => 199],
        ],
        'yearning' => [
            ['surah' => 2, 'ayah' => 186],
            ['surah' => 28, 'ayah' => 24],
            ['surah' => 25, 'ayah' => 74],
            ['surah' => 30, 'ayah' => 21],
            ['surah' => 24, 'ayah' => 32],
            ['surah' => 93, 'ayah' => 3],
        ],
    ];

    private array $crisisFallback = [
        ['surah' => 2, 'ayah' => 195],
        ['surah' => 4, 'ayah' => 29],
        ['surah' => 39, 'ayah' => 53],
        ['surah' => 12, 'ayah' => 87],
        ['surah' => 94, 'ayah' => 5],
        ['surah' => 94, 'ayah' => 6],
    ];

    private const SUPPORT_RESOURCES = [
        'arabic' => 'إذا كنت تفكر في إيذاء نفسك، فأرجوك تواصل مع شخص تثق به أو مع خط المساعدة النفسية. أنت لست وحدك. الحياة ثمينة وهناك دائما أمل.',
        'english' => 'If you are thinking about harming yourself, please reach out to someone you trust or contact a crisis helpline. You are not alone. Life is precious and there is always hope.',
    ];

    public function handleInput(Request $request): JsonResponse
    {
        $request->validate([
            'input' => 'required|string|min:2|max:2000',
        ]);

        $userInput = $request->input('input');

        $classification = $this->classifyWithOpenAI($userInput);

        if (($classification['status'] ?? null) === 'rejected') {
            return response()->json([
                'status' => 'rejected',
                'message' => 'I cannot provide legal rulings, fatwas, medical advice, or halal/haram judgments. Please ask a question about your feelings or personal reflections.',
            ]);
        }

        $category = $classification['category'] ?? null;
        $language = $classification['language'] ?? 'english';
        $reflection = $classification['reflection'] ?? null;
        $isCrisis = $classification['crisis'] ?? false;
        $suggestedVerses = $classification['verses'] ?? [];

        if ($isCrisis) {
            $verses = $this->fetchIslamicAppVerses($suggestedVerses, $language)
                ?: $this->fetchFallbackVerses($this->crisisFallback, $language);

            return response()->json([
                'status' => 'approved',
                'category' => 'crisis',
                'language' => $language,
                'crisis' => true,
                'reflection' => $reflection,
                'verses' => $verses,
                'supportResources' => [
                    'message' => self::SUPPORT_RESOURCES[$language] ?? self::SUPPORT_RESOURCES['english'],
                ],
            ]);
        }

        $verses = $this->fetchIslamicAppVerses($suggestedVerses, $language);

        if (empty($verses) && $category && isset($this->fallbackVerseMap[$category])) {
            $verses = $this->fetchFallbackVerses($this->fallbackVerseMap[$category], $language);
        }

        if (empty($verses)) {
            $category = 'sadness';
            $language = 'english';
            $verses = $this->fetchFallbackVerses($this->fallbackVerseMap['sadness'], 'english');
        }

        return response()->json([
            'status' => 'approved',
            'category' => $category,
            'language' => $language,
            'reflection' => $reflection,
            'verses' => $verses,
        ]);
    }

    private function classifyWithOpenAI(string $input): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post(self::OPENAI_ENDPOINT, [
            'model' => 'gpt-4o',
            'temperature' => 0.1,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Return JSON: category, language, crisis (bool), reflection, verses (array of {surah, ayah}).

TOPIC CATEGORY — pick ONE:
- sadness: talking about depression, sadness, heartbreak, disappointment, hopelessness, crying, despair, feeling low
- anxiety: talking about fear, worry, stress, panic, being overwhelmed, nervous, scared, anxious about future
- loneliness: talking about being alone, isolated, no friends/family, abandoned, nobody cares, no support
- financial_worry: talking about money problems, debt, poverty, broke, need provision/rizq, bills, income, financial struggle
- yearning: talking about wanting marriage, a spouse, children, love, relationship, connection, longing to get married
- grief: talking about death of a loved one, loss, mourning, missing someone who died, bereavement
- guilt: talking about sin, regret, shame, remorse, repentance, asking forgiveness, "I did wrong"
- anger: talking about angry, furious, irritated, frustrated, resentful, rage
- happiness: talking about happy, joyful, excited, delighted, celebrating
- gratitude: talking about thankful, grateful, blessed, appreciative, shukr, alhamdulillah
- hope: talking about optimistic, hoping for good, trusting Allah, looking forward to positive change
- contentment: talking about peace, calm, satisfaction, tranquility, at peace, qalbi mutma\'inn
- rejected: ONLY for asking halal/haram rules, fatwa, Islamic legal rulings, medical diagnosis, legal advice

STRICT VERSE RELEVANCE RULES:
Every verse you suggest MUST be DIRECTLY about the user\'s specific topic. Look at what the user actually said and pick verses whose central theme matches.

KNOW WHAT EACH VERSE ACTUALLY SAYS. Do not guess. Only use verses you are certain about.
- 30:21 = "He created for you mates from among yourselves that you may find tranquility in them; He placed love and mercy between you" → DIRECTLY about marriage
- 25:74 = "Our Lord, grant us from among our spouses and offspring comfort to our eyes" → DIRECTLY about marriage/children
- 24:32 = "Marry the unmarried among you" → DIRECTLY about marriage
- 16:72 = "Allah made for you from yourselves mates and from your mates children" → DIRECTLY about marriage
- 2:187 = "They are clothing for you and you are clothing for them" → DIRECTLY about spouses
- 30:4 = "To Allah belongs the command before and after" → NOT about happiness, WRONG for happiness topic
- 20:20 = "He threw his staff and it became a snake" → NOT about marriage, DO NOT use for yearning
- 2:186 = "I am near, I answer the call" → about dua, NOT directly about marriage/loneliness
- 93:3 = "Your Lord has not forsaken you" → general comfort, NOT directly about marriage/sadness
- 28:24 = "My Lord, I am in need of whatever good You send" → general need, NOT directly about marriage

CORRECT EXAMPLES of topic → verses (ALL verses must be ON-TOPIC):
- "i need money" (financial_worry) → 65:2, 65:3, 11:6, 51:58, 2:261, 67:15 (all about provision/rizq)
- "أريد أن أتزوج" (yearning/marriage) → 30:21, 25:74, 24:32, 16:72, 2:187 (ALL directly about marriage/spouses)
- "im so depressed" (sadness) → 2:286, 39:53, 12:87, 94:5, 94:6, 3:139 (all about comfort/relief)
- "i want to kill myself" (crisis) → 2:195, 4:29, 4:30, 39:53, 12:87, 94:5 (quotes about NOT killing yourself + hope)
- "im happy" (happiness) → 10:58, 27:19, 30:4, 3:139, 15:98 (all about joy/celebration)
- "i feel lonely" (loneliness) → 2:186, 9:40, 50:16, 20:46, 28:7 (all about Allah being with you)
- "i miss my mom who died" (grief) → 2:156, 2:157, 16:127, 22:35 (all about patience in loss)
- "i sinned" (guilt) → 39:53, 42:25, 66:8, 25:70, 9:104 (all about forgiveness/repentance)
- "im scared" (anxiety) → 13:28, 65:2, 65:3, 29:69, 8:2 (all about finding peace in Allah)
- "thank you Allah" (gratitude) → 14:7, 55:13, 16:18, 2:152, 31:12 (all about thankfulness)
- "im angry" (anger) → 3:134, 42:37, 42:40, 25:63, 7:199 (all about controlling anger)

BAD EXAMPLES (WRONG — do not do this):
- For "أريد أن أتزوج", do NOT include 2:186 (Allah is near — NOT about marriage) or 93:3 (Allah hasn\'t forsaken you — NOT about marriage)
- For "أشعر باكتئاب شديد", do NOT include 2:186 or 93:3 unless the user is ALSO feeling abandoned by Allah

CORRECT BEHAVIOR: If user says "أريد أن أتزوج", ALL 5-6 verses must be about marriage/spouses/ nikah. NO general verses. Only 30:21, 25:74, 24:32, 16:72, 2:187, 42:11, 7:189, 4:1, 2:228, 4:19, etc.

LANGUAGE: "arabic" if input contains Arabic script, "english" otherwise.

CRISIS: true ONLY if user talks about suicide, self-harm, wanting to die, ending their life.

REFLECTION: Write 1 warm sentence about their specific topic in their language.

RULES:
1. Emotions and life struggles are ALWAYS approved.
2. loneliness = alone/isolated. Money = financial_worry. Marriage = yearning.
3. Every verse MUST be REAL (surah 1-114, ayah >= 1).
4. Default to "sadness" if unsure. NEVER default to rejected for emotions.',
                ],
                [
                    'role' => 'user',
                    'content' => $input,
                ],
            ],
        ]);

        if ($response->failed()) {
            return ['status' => 'rejected', 'category' => null, 'crisis' => false, 'verses' => []];
        }

        $body = $response->json();
        $content = json_decode($body['choices'][0]['message']['content'] ?? '{}', true);

        $category = $content['category'] ?? null;
        $status = $content['status'] ?? 'approved';

        if ($status === 'rejected' || $category === 'rejected') {
            return ['status' => 'rejected', 'category' => null, 'crisis' => false, 'verses' => []];
        }

        $verses = [];
        if (isset($content['verses']) && is_array($content['verses'])) {
            foreach ($content['verses'] as $v) {
                $surah = (int)($v['surah'] ?? 0);
                $ayah = (int)($v['ayah'] ?? 0);
                if ($surah >= 1 && $surah <= 114 && $ayah >= 1) {
                    $verses[] = ['surah' => $surah, 'ayah' => $ayah];
                }
            }
        }

        return [
            'status' => 'approved',
            'category' => $category,
            'language' => $content['language'] ?? 'english',
            'reflection' => $content['reflection'] ?? null,
            'crisis' => $content['crisis'] ?? false,
            'verses' => $verses,
        ];
    }

    private function fetchIslamicAppVerses(array $references, string $language): array
    {
        if (empty($references)) {
            return [];
        }

        $translation = $language === 'arabic'
            ? 'ar-quran-uthmani'
            : 'en-sahih-international';

        $verses = [];
        foreach ($references as $ref) {
            $surah = $ref['surah'];
            $ayah = $ref['ayah'];
            $reference = "{$surah}:{$ayah}";

            $response = Http::get(sprintf(self::ISLAMIC_APP_VERSES, $surah, $ayah), [
                'fields' => 'text_uthmani',
                'translations' => $translation,
            ]);

            if (!$response->successful()) {
                continue;
            }

            $data = $response->json();
            $verse = $data['data']['verse'] ?? [];

            $text = '';
            if ($language === 'arabic') {
                $text = $verse['text_uthmani'] ?? '';
            } else {
                $text = $verse['translations'][0]['text'] ?? $verse['text_uthmani'] ?? '';
            }

            if (!empty($text)) {
                $verses[] = [
                    'reference' => $reference,
                    'text' => $text,
                    'surah' => $surah,
                    'ayah' => $ayah,
                ];
            }
        }

        return $verses;
    }

    private function fetchFallbackVerses(array $references, string $language): array
    {
        $hash = crc32(json_encode($references) . $language . date('Y-m-d'));
        $count = count($references);
        $numToSelect = min(3, $count);

        $selectedKeys = [];
        for ($i = 0; $i < $numToSelect; $i++) {
            $key = abs($hash + $i * 7) % $count;
            if (!in_array($key, $selectedKeys)) {
                $selectedKeys[] = $key;
            } else {
                for ($j = 0; $j < $count; $j++) {
                    if (!in_array($j, $selectedKeys)) {
                        $selectedKeys[] = $j;
                        break;
                    }
                }
            }
        }

        $selectedRefs = [];
        foreach ($selectedKeys as $key) {
            $selectedRefs[] = $references[$key];
        }

        return $this->fetchIslamicAppVerses($selectedRefs, $language);
    }
}
