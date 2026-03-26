<?php

namespace App\Jobs;

use App\Models\Plat;
use App\Models\User;
use App\Models\Recommendation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateRecommendationJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, Queueable, InteractsWithQueue;

    public int $timeout = 90;
    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    public function __construct(
        protected int $platId,
        protected int $userId,
    ) {}

    public function uniqueId()
    {
        return $this->userId . '-' . $this->platId;
    }


    public function handle(): void
    {
        $plat = Plat::with('ingredients')->find($this->platId);
        $user = User::find($this->userId);

        if (!$plat || !$user) {
            $this->fail(new \Exception("Plat ou User introuvable"));
            return;
        }

        $ingredients = $plat->ingredients->pluck('tag')->toArray();
        $restrictions = $user->dietary_tags ?? [];

        // 🔥 logique locale
        $conflicts = $this->detectConflicts($ingredients, $restrictions);
        $score = max(0, 100 - (count($conflicts) * 25));

        $warningMessage = null;

        if ($score < 50) {
            $prompt = $this->buildPrompt(
                $plat->title,
                implode(', ', $ingredients),
                implode(', ', $restrictions)
            );

            $response = Http::withToken(config('services.groq.key'))
                ->timeout(30)
                ->post(config('services.groq.url'), [
                    'inputs' => $prompt
                ]);

            if (!$response->failed()) {
                $data = $response->json();
                $warningMessage = $data[0]['generated_text'] ?? null;
            }
        }

        $result = $this->buildResult($score, $warningMessage);

        $this->saveResult(
            score: $result['score'],
            label: $result['label'],
            warningMessage: $result['warning_message'],
            conflicts: $conflicts,
            status: 'ready'
        );
    }

    // =========================
    // 🔥 PROMPT
    // =========================
    private function buildPrompt(string $platName, string $ingredients, string $restrictions): string
    {
        return  <<<PROMPT
            Analyze the nutritional compatibility between this dish and the user's dietary restrictions.

            DISH: {$platName}
            INGREDIENT TAGS: {$ingredients}
            USER RESTRICTIONS: {$restrictions}

            Tag mapping rules:
            "vegan" restriction conflicts with: contains_meat, contains_lactose
            "no_sugar" restriction conflicts with: contains_sugar
            "no_cholesterol" restriction conflicts with: contains_cholesterol
            "gluten_free" restriction conflicts with: contains_gluten
            "no_lactose" restriction conflicts with: contains_lactose

            Calculate score: start at 100, subtract 25 for each conflict found.

            Respond ONLY with this JSON (no markdown, no explanation):
            {"score": <0-100>, "warning_message": "<in French if score < 50, else empty string>"}
            PROMPT;
    }

    // =========================
    //  PARSE AI RESPONSE
    // =========================
    private function parseResponse(string $text): array
    {
        $text = trim($text);

        // 1. <output>
        if (preg_match('/<output>(.*?)<\/output>/s', $text, $m)) {
            $decoded = $this->decodeJson($m[1]);
            if ($decoded) return $decoded;
        }

        // 2. JSON fallback
        if (preg_match('/\{.*"score".*\}/s', $text, $m)) {
            $decoded = $this->decodeJson($m[0]);
            if ($decoded) return $decoded;
        }

        // 3. number fallback
        if (preg_match('/\d{1,3}/', $text, $m)) {
            $score = (int) $m[0];
            return $this->buildResult($score, null);
        }

        return $this->buildResult(50, 'Analyse incertaine');
    }

    private function decodeJson(string $json): ?array
    {
        $data = json_decode(trim($json), true);

        if (!$data || !isset($data['score'])) {
            return null;
        }

        $score = max(0, min(100, (int)$data['score']));
        $warning = $data['warning_message'] ?? null;

        return $this->buildResult($score, $warning);
    }

    // =========================
    // 🔥 BUSINESS LOGIC
    // =========================
    private function buildResult(int $score, ?string $warning): array
    {
        $label = match (true) {
            $score >= 80 => '✅ Highly Recommended',
            $score >= 50 => '🟡 Recommended',
            default => '⚠️ Not Recommended',
        };

        return [
            'score' => $score,
            'label' => $label,
            'warning_message' => $score < 50 ? $warning : null,
        ];
    }

    // =========================
    // 🔥 CONFLICT DETECTION
    // =========================
    private function detectConflicts(array $ingredients, array $restrictions): array
    {
        $rules = [
            'vegan' => ['contains_meat', 'contains_lactose'],
            'no_sugar' => ['contains_sugar'],
            'no_cholesterol' => ['contains_cholesterol'],
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

        return array_values(array_unique($conflicts));
    }

    // =========================
    // 🔥 SAVE RESULT
    // =========================
    private function saveResult(
        int $score,
        string $label,
        ?string $warningMessage,
        array $conflicts,
        string $status
    ): void {
        Recommendation::where([
            'user_id' => $this->userId,
            'plat_id' => $this->platId
        ])->update([
            'score' => $score,
            'label' => $label,
            'warning_message' => $warningMessage,
            'conflicting_tags' => json_encode($conflicts),
            'status' => $status,
        ]);
    }

    // =========================
    // 🔥 FINAL FAILURE
    // =========================
    public function failed(\Throwable $exception): void
    {
        Log::error('Recommendation Job Failed', [
            'error' => $exception->getMessage()
        ]);

        $this->saveResult(
            score: 0,
            label: '⚠️ Not Recommended',
            warningMessage: 'Service indisponible',
            conflicts: [],
            status: 'failed'
        );
    }
}
