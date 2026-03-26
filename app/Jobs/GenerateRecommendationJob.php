<?php

namespace App\Jobs;

use App\Models\Plat;
use App\Models\User;
use App\Models\Recommendation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateRecommendationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public int $userId,
        public int $platId
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        $plat = Plat::with('ingredients')->find($this->platId);

        if (!$user || !$plat) {
            $this->fail(new \Exception("User or Plat not found"));
            return;
        }

        $ingredients = $plat->ingredients->pluck('tag')->toArray();
        $restrictions = $user->dietary_tags ?? [];

        // ✅ Score backend (FIABLE)
        $conflicts = $this->detectConflicts($ingredients, $restrictions);
        $score = max(0, 100 - (count($conflicts) * 25));

        // 🔥 Toujours appeler GROQ pour message
        $aiResult = $this->callGroq($plat->title, $ingredients, $restrictions);

        // ✅ fallback si IA échoue
        $warningMessage = $aiResult['warning_message']
            ?? $this->generateFallbackMessage($score, $conflicts);

        // ✅ résultat final
        $result = $this->buildResult($score, $warningMessage);

        Recommendation::updateOrCreate(
            [
                'user_id' => $user->id,
                'plat_id' => $plat->id
            ],
            [
                'score' => $result['score'],
                'label' => $result['label'],
                'warning_message' => $result['warning_message'],
                'conflicting_tags' => $conflicts,
                'status' => Recommendation::STATUS_READY,
            ]
        );
    }

    // =========================
    // 🔥 GROQ CALL
    // =========================
    private function callGroq(string $platName, array $ingredients, array $restrictions): ?array
    {
        try {
            $response = Http::withToken(config('services.groq.key'))
                ->timeout(20)
                ->post(config('services.groq.url'), [
                    "model" => "llama3-8b-8192",
                    "messages" => [
                        [
                            "role" => "user",
                            "content" => $this->buildPrompt($platName, $ingredients, $restrictions)
                        ]
                    ],
                    "temperature" => 0.2
                ]);

            if ($response->failed()) return null;

            $text = $response->json('choices.0.message.content');

            return $this->parseResponse($text);
        } catch (\Throwable $e) {
            return null;
        }
    }

    // =========================
    // 🔥 PROMPT
    // =========================
    private function buildPrompt(string $platName, array $ingredients, array $restrictions): string
    {
        return <<<PROMPT
        You are a food safety and nutrition expert.

        Analyze if the following dish is compatible with the user's dietary restrictions.

        Dish: {$platName}
        Ingredients: " . implode(', ', $ingredients) . "
        Restrictions: " . implode(', ', $restrictions) . "

        Rules:
        - Start score at 100
        - Subtract 25 for each conflict
        - Score must be between 0 and 100
        - Be slightly lenient (do not over-penalize)
        - If score < 50 → provide a clear warning in French
        - If score >= 50 → warning_message must be null

        Respond ONLY in valid JSON format:
        {
        "score": number,
        "warning_message": string|null
        }
        PROMPT;
    }

    // =========================
    // 🔥 PARSE
    // =========================
    private function parseResponse(?string $text): array
    {
        if (!$text) {
            return [
                'score' => 50,
                'warning_message' => 'Analyse effectuée'
            ];
        }

        // extraire JSON
        if (preg_match('/\{.*\}/s', $text, $match)) {
            $data = json_decode($match[0], true);

            if ($data && isset($data['score'])) {
                $score = max(0, min(100, (int)$data['score']));
                $warning = $data['warning_message'] ?? 'Analyse effectuée';

                return [
                    'score' => $score,
                    'warning_message' => $warning
                ];
            }
        }

        return [
            'score' => 50,
            'warning_message' => 'Analyse incertaine'
        ];
    }

    // =========================
    // 🔥 LOGIC
    // =========================
    private function detectConflicts(array $ingredients, array $restrictions): array
    {
        $rules = [
            'vegan' => ['contains_meat', 'contains_lactose'],
            'no_sugar' => ['contains_sugar'],
            'gluten_free' => ['contains_gluten'],
            'no_lactose' => ['contains_lactose'],
        ];

        $conflicts = [];

        foreach ($restrictions as $restriction) {
            if (!isset($rules[$restriction])) continue;

            foreach ($rules[$restriction] as $tag) {
                if (in_array($tag, $ingredients)) {
                    $conflicts[] = $tag;
                }
            }
        }

        return array_unique($conflicts);
    }

    private function buildResult(int $score, ?string $warning): array
    {
        return [
            'score' => $score,
            'label' => match (true) {
                $score >= 80 => '✅ Highly Recommended',
                $score >= 50 => '🟡 Recommended',
                default => '⚠️ Not Recommended',
            },
            'warning_message' => $warning, // 🔥 TOUJOURS stocké
        ];
    }

    public function failed(\Throwable $e)
    {
        Log::error('Job failed', ['error' => $e->getMessage()]);

        Recommendation::where([
            'user_id' => $this->userId,
            'plat_id' => $this->platId
        ])->update([
            'status' => Recommendation::STATUS_FAILED,
            'score' => 0
        ]);
    }


    private function generateFallbackMessage(int $score, array $conflicts): string
    {
        if (empty($conflicts)) {
            return "Ce plat semble compatible avec votre régime alimentaire.";
        }

        return "Attention : ce plat contient " . implode(', ', $conflicts) .
            " ce qui peut être incompatible avec votre régime.";
    }
}
