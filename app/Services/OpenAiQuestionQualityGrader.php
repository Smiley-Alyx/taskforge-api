<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
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

        $response = Http::baseUrl($baseUrl)
            ->withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->post('/responses', [
                'model' => $model,
                'input' => $this->buildPrompt($question),
                'temperature' => 0,
            ]);

        try {
            $response->throw();
        } catch (ConnectionException|RequestException $exception) {
            throw new RuntimeException('OpenAI request failed.', previous: $exception);
        }

        return $this->extractGradeFromResponse($response->json());
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
        if (preg_match('/-?\d+/', $value, $matches) !== 1) {
            return null;
        }

        return (int) $matches[0];
    }
}
