<?php

namespace App\Console\Commands;

use App\Services\HeuristicQuestionQualityGrader;
use Illuminate\Console\Command;
use RuntimeException;

class GradeQuestionsCommand extends Command
{
    protected $signature = 'grade:questions';

    protected $description = 'Grades exam question quality and writes grades.json';

    public function __construct(private readonly HeuristicQuestionQualityGrader $grader)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $questions = $this->readQuestions();
        $results = [];

        foreach ($questions as $index => $question) {
            if (! is_array($question)) {
                throw new RuntimeException("Question at index {$index} must be an object.");
            }

            $id = $question['id'] ?? null;
            if (! is_string($id) || trim($id) === '') {
                throw new RuntimeException("Question at index {$index} has an invalid id.");
            }

            if (array_key_exists($id, $results)) {
                throw new RuntimeException("Duplicate question id detected: {$id}.");
            }

            $results[$id] = $this->normalizeGrade($this->grader->grade($question));
        }

        $payload = ['results' => $results];
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (! is_string($json)) {
            throw new RuntimeException('Unable to encode grades payload to JSON.');
        }

        $written = file_put_contents(base_path('grades.json'), $json.PHP_EOL);
        if ($written === false) {
            throw new RuntimeException('Unable to write grades.json file.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readQuestions(): array
    {
        $raw = file_get_contents(base_path('questions.json'));
        if ($raw === false) {
            throw new RuntimeException('Unable to read questions.json file.');
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('questions.json must contain an array of question objects.');
        }

        return $decoded;
    }

    private function normalizeGrade(mixed $grade): int
    {
        if (! is_int($grade)) {
            $grade = (int) $grade;
        }

        return max(1, min(10, $grade));
    }
}
