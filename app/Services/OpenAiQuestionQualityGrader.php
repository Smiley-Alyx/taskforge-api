<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiQuestionQualityGrader
{
    /**
     * @param  array{id?: mixed, question?: mixed, answer?: mixed, distractors?: mixed, explanation?: mixed}  $question
     */
    public function grade(array $question): ?int
    {
        $apiKey = (string) config('services.openai.key', '');
        if ($apiKey === '') {
            return null;
        }

        $baseUrl = rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/');
        $model = (string) config('services.openai.model', 'gpt-5.5');
        $maxAttempts = max(1, (int) config('services.openai.max_attempts', 3));
        $requestTimeout = max(5, (int) config('services.openai.timeout_seconds', 30));
        $backoffMs = max(0, (int) config('services.openai.backoff_ms', 400));
        $prompt = $this->buildPrompt($question);

        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = $this->sendRequest(
                    baseUrl: $baseUrl,
                    apiKey: $apiKey,
                    model: $model,
                    prompt: $prompt,
                    timeoutSeconds: $requestTimeout
                );

                $grade = $this->extractGradeFromResponse($response->json());
                if ($grade !== null) {
                    return $grade;
                }
            } catch (ConnectionException|RequestException $exception) {
                $lastException = $exception;
            }

            if ($attempt < $maxAttempts && $backoffMs > 0) {
                usleep($backoffMs * 1000 * $attempt);
            }
        }

        if ($lastException !== null) {
            throw new RuntimeException('OpenAI request failed after retries.', previous: $lastException);
        }

        return null;
    }

    /**
     * @param  array{id?: mixed, question?: mixed, answer?: mixed, distractors?: mixed, explanation?: mixed}  $question
     */
    private function buildPrompt(array $question): string
    {
        $distractors = $question['distractors'] ?? [];
        if (! is_array($distractors)) {
            $distractors = [];
        }

        $preparedDistractors = [];
        foreach ($distractors as $option) {
            if (is_string($option) && trim($option) !== '') {
                $preparedDistractors[] = trim($option);
            }
        }

        $payload = [
            'id' => $question['id'] ?? null,
            'question' => $question['question'] ?? null,
            'answer' => $question['answer'] ?? null,
            'distractors' => array_values($preparedDistractors),
            'explanation' => $question['explanation'] ?? null,
        ];

        $questionJson = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (! is_string($questionJson)) {
            throw new RuntimeException('Unable to encode question payload for OpenAI prompt.');
        }

        return <<<PROMPT
You are grading the quality of a multiple-choice certification exam question.
Return only one integer from 1 to 10, where:
- 1 means very poor question quality;
- 10 means excellent question quality.

Assess quality using your own rubric (clarity, realism, discrimination power, plausible distractors, alignment between stem/options/explanation, absence of ambiguity or giveaway cues).

Output requirements:
- Return only the integer.
- No words, no JSON, no punctuation.

Question payload:
{$questionJson}
PROMPT;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function extractGradeFromResponse(?array $payload): ?int
    {
        if (! is_array($payload)) {
            return null;
        }

        $outputText = trim((string) ($payload['output_text'] ?? ''));
        if ($outputText !== '') {
            $fromText = $this->extractInt($outputText);
            if ($fromText !== null) {
                return $fromText;
            }
        }

        $content = $payload['output'][0]['content'][0]['text'] ?? null;
        if (is_string($content) && trim($content) !== '') {
            return $this->extractInt($content);
        }

        return null;
    }

    private function extractInt(string $value): ?int
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/^\s*(-?\d+)\s*$/', $trimmed, $strictMatch) === 1) {
            return (int) $strictMatch[1];
        }

        if (preg_match('/-?\d+/', $trimmed, $fallbackMatch) === 1) {
            return (int) $fallbackMatch[0];
        }

        return null;
    }

    private function sendRequest(
        string $baseUrl,
        string $apiKey,
        string $model,
        string $prompt,
        int $timeoutSeconds
    ): Response {
        $response = Http::baseUrl($baseUrl)
            ->withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout($timeoutSeconds)
            ->post('/responses', [
                'model' => $model,
                'input' => $prompt,
                'temperature' => 0,
                'max_output_tokens' => 8,
            ]);

        $response->throw();

        return $response;
    }
}
