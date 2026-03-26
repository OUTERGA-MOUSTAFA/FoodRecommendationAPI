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

        // 🔥 score local
        $conflicts = $this->detectConflicts($ingredients, $restrictions);
        $score = max(0, 100 - (count($conflicts) * 25));

        $warningMessage = null;

        // 🔥 appel GROQ seulement si mauvais score
        if ($score < 50) {
            $warningMessage = $this->callGroq($plat->title, $ingredients, $restrictions);
        }

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
    private function callGroq(string $platName, array $ingredients, array $restrictions): ?string
    {
        try {
            $response = Http::withToken(config('services.groq.key'))
                ->timeout(20)
                ->post(config('services.groq.url'), [
                    "messages" => [
                        [
                            "role" => "system",
                            "content" => "Tu es un expert en nutrition."
                        ],
                        [
                            "role" => "user",
                            "content" => $this->buildPrompt($platName, $ingredients, $restrictions)
                        ]
                    ],
                    "temperature" => 0.3
                ]);

            if ($response->failed()) {
                Log::error('Groq API failed', ['body' => $response->body()]);
                return null;
            }

            $text = $response->json('choices.0.message.content');

            return $this->parseResponse($text);

        } catch (\Throwable $e) {
            Log::error('Groq Exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // =========================
    // 🔥 PROMPT
    // =========================
    private function buildPrompt(string $platName, array $ingredients, array $restrictions): string
    {
        return "
        Plat: {$platName}
        Ingredients: " . implode(', ', $ingredients) . "
        Restrictions: " . implode(', ', $restrictions) . "

        Explique en français pourquoi ce plat n'est pas recommandé.
        Réponse courte (1 phrase).
        ";
    }

    // =========================
    // 🔥 PARSE
    // =========================
    private function parseResponse(?string $text): ?string
    {
        if (!$text) return null;

        return trim($text);
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
            'warning_message' => $score < 50 ? $warning : null,
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
}