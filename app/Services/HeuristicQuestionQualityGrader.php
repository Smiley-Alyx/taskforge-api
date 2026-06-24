<?php

namespace App\Services;

class HeuristicQuestionQualityGrader
{
    /**
     * @param  array{id?: mixed, question?: mixed, answer?: mixed, distractors?: mixed, explanation?: mixed}  $question
     */
    public function grade(array $question): int
    {
        $stem = $this->normalizeText($question['question'] ?? '');
        $answer = $this->normalizeText($question['answer'] ?? '');
        $explanation = $this->normalizeText($question['explanation'] ?? '');
        $distractors = $this->normalizeDistractors($question['distractors'] ?? []);

        $score = 5;

        $stemWordCount = $this->wordCount($stem);
        if ($stemWordCount >= 18) {
            $score += 1;
        }
        if ($stemWordCount >= 35) {
            $score += 1;
        }
        if ($stemWordCount <= 6) {
            $score -= 2;
        }

        $explanationWordCount = $this->wordCount($explanation);
        if ($explanationWordCount >= 14) {
            $score += 1;
        }
        if ($explanationWordCount <= 5) {
            $score -= 1;
        }

        if ($answer !== '' && str_contains(mb_strtolower($stem), mb_strtolower($answer))) {
            $score -= 2;
        }

        if (count($distractors) < 3) {
            $score -= 2;
        }

        $uniqueDistractors = array_values(array_unique(array_map('mb_strtolower', $distractors)));
        if (count($uniqueDistractors) !== count($distractors)) {
            $score -= 1;
        }

        foreach ($distractors as $distractor) {
            if ($distractor === '' || mb_strtolower($distractor) === mb_strtolower($answer)) {
                $score -= 1;
            }
        }

        return max(1, min(10, $score));
    }

    private function normalizeText(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }

    /**
     * @param  mixed  $value
     * @return array<int, string>
     */
    private function normalizeDistractors(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $normalized = [];
        foreach ($value as $item) {
            if (! is_string($item)) {
                continue;
            }

            $text = trim(preg_replace('/\s+/u', ' ', $item) ?? '');
            if ($text !== '') {
                $normalized[] = $text;
            }
        }

        return $normalized;
    }

    private function wordCount(string $text): int
    {
        if ($text === '') {
            return 0;
        }

        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        return is_array($words) ? count($words) : 0;
    }
}
