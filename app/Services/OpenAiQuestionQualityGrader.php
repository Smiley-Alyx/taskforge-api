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
        $id = $question['id'] ?? null;
        if (! is_string($id) || trim($id) === '') {
            return null;
        }

        $grades = $this->gradeBatch([$question]);

        return $grades[$id] ?? null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     * @return array<string, int>
     */
    public function gradeBatch(array $questions): array
    {
        $apiKey = (string) config('services.openai.key', '');
        if ($apiKey === '' || $questions === []) {
            return [];
        }

        $baseUrl = rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/');
        $model = (string) config('services.openai.model', 'gpt-5.5');
        $maxAttempts = max(1, (int) config('services.openai.max_attempts', 3));
        $requestTimeout = max(5, (int) config('services.openai.timeout_seconds', 30));
        $backoffMs = max(0, (int) config('services.openai.backoff_ms', 400));
        $maxOutputTokens = max(16, (int) config('services.openai.max_output_tokens', 512));
        $prompt = $this->buildBatchPrompt($questions);
        $allowedIds = $this->extractAllowedIds($questions);

        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = $this->sendRequest(
                    baseUrl: $baseUrl,
                    apiKey: $apiKey,
                    model: $model,
                    prompt: $prompt,
                    timeoutSeconds: $requestTimeout,
                    maxOutputTokens: $maxOutputTokens
                );

                $grades = $this->extractBatchGradesFromResponse($response->json(), $allowedIds);
                if ($grades !== []) {
                    return $grades;
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

        return [];
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     */
    private function buildBatchPrompt(array $questions): string
    {
        $preparedQuestions = [];
        foreach ($questions as $question) {
            $id = $question['id'] ?? null;
            if (! is_string($id) || trim($id) === '') {
                continue;
            }

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

            $preparedQuestions[] = [
                'id' => $id,
                'question' => $question['question'] ?? null,
                'answer' => $question['answer'] ?? null,
                'distractors' => array_values($preparedDistractors),
                'explanation' => $question['explanation'] ?? null,
            ];
        }

        $questionsJson = json_encode($preparedQuestions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (! is_string($questionsJson)) {
            throw new RuntimeException('Unable to encode questions payload for OpenAI prompt.');
        }

        return <<<PROMPT
Grade multiple-choice certification exam question quality.
Return JSON only with integer grades 1..10.

Rules:
- Grade each id from 1 (poor) to 10 (excellent).
- Assume target exam is SPHR (Senior Professional in Human Resources).
- Domain relevance is mandatory: if a question is mostly outside HR/SPHR scope, apply a severe penalty.
- Off-topic non-HR questions should generally score 1..3 even if technically well-written.
- Use ids exactly as provided.
- Return all ids once.
- No text outside JSON.
- Format: {"results":{"id1":7,"id2":3}}

Questions:
{$questionsJson}
PROMPT;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function extractBatchGradesFromResponse(?array $payload, array $allowedIds): array
    {
        if (! is_array($payload)) {
            return [];
        }

        $text = trim((string) ($payload['output_text'] ?? ''));
        if ($text === '') {
            $text = $this->extractContentText($payload);
        }

        if ($text === '') {
            return [];
        }

        $decoded = $this->decodeJson($text);
        if (! is_array($decoded)) {
            return [];
        }

        $results = $decoded['results'] ?? $decoded;
        if (! is_array($results)) {
            return [];
        }

        $allowed = array_fill_keys($allowedIds, true);
        $grades = [];

        foreach ($results as $id => $gradeValue) {
            if (! is_string($id) || ! isset($allowed[$id])) {
                continue;
            }

            $grade = null;
            if (is_int($gradeValue) || is_float($gradeValue) || is_string($gradeValue)) {
                $grade = $this->extractInt((string) $gradeValue);
            }

            if ($grade !== null) {
                $grades[$id] = max(1, min(10, $grade));
            }
        }

        return $grades;
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

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractContentText(array $payload): string
    {
        $output = $payload['output'] ?? null;
        if (! is_array($output)) {
            return '';
        }

        foreach ($output as $item) {
            if (! is_array($item)) {
                continue;
            }

            $content = $item['content'] ?? null;
            if (! is_array($content)) {
                continue;
            }

            foreach ($content as $part) {
                if (is_array($part) && isset($part['text']) && is_string($part['text']) && trim($part['text']) !== '') {
                    return trim($part['text']);
                }
            }
        }

        return '';
    }

    private function decodeJson(string $text): ?array
    {
        $candidates = [$text, $this->stripJsonCodeFence($text)];

        foreach ($candidates as $candidate) {
            $decoded = json_decode($candidate, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function stripJsonCodeFence(string $text): string
    {
        $trimmed = trim($text);

        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/si', $trimmed, $matches) === 1) {
            return trim($matches[1]);
        }

        return $trimmed;
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     * @return array<int, string>
     */
    private function extractAllowedIds(array $questions): array
    {
        $ids = [];
        foreach ($questions as $question) {
            $id = $question['id'] ?? null;
            if (is_string($id) && trim($id) !== '') {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function sendRequest(
        string $baseUrl,
        string $apiKey,
        string $model,
        string $prompt,
        int $timeoutSeconds,
        int $maxOutputTokens
    ): Response {
        $response = Http::baseUrl($baseUrl)
            ->withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout($timeoutSeconds)
            ->post('/responses', [
                'model' => $model,
                'input' => $prompt,
                'max_output_tokens' => $maxOutputTokens,
                'reasoning' => [
                    'effort' => 'low',
                ],
                'text' => [
                    'format' => [
                        'type' => 'json_object',
                    ],
                ],
            ]);

        $response->throw();

        return $response;
    }
}
