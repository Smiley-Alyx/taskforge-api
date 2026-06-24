<?php

namespace App\Console\Commands;

use App\Services\HeuristicQuestionQualityGrader;
use App\Services\OpenAiQuestionQualityGrader;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\Command;
use Throwable;
use RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class GradeQuestionsCommand extends Command
{
    protected $signature = 'grade:questions {--progress : Show progress in stderr output}';

    protected $description = 'Grades exam question quality and writes grades.json';

    public function __construct(
        private readonly OpenAiQuestionQualityGrader $openAiGrader,
        private readonly HeuristicQuestionQualityGrader $heuristicGrader
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $questions = $this->validateQuestions($this->readQuestions());
        $results = [];
        $llmCount = 0;
        $fallbackCount = 0;

        $progressBar = $this->makeProgressBar(count($questions));
        $batchSize = $this->batchSize();

        foreach (array_chunk($questions, $batchSize) as $chunk) {
            $llmGrades = [];
            try {
                $llmGrades = $this->openAiGrader->gradeBatch($chunk);
            } catch (Throwable) {
                $llmGrades = [];
            }

            foreach ($chunk as $question) {
                $id = (string) $question['id'];

                if (array_key_exists($id, $llmGrades)) {
                    $results[$id] = $this->normalizeGrade($llmGrades[$id]);
                    $llmCount++;
                } else {
                    $results[$id] = $this->normalizeGrade($this->heuristicGrader->grade($question));
                    $fallbackCount++;
                }

                $progressBar?->advance();
            }
        }

        $progressBar?->finish();
        if ($progressBar !== null) {
            $this->errorOutput()->writeln('');
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

        if ($this->option('progress')) {
            $total = count($results);
            $this->errorOutput()->writeln(sprintf(
                'Graded %d questions. LLM: %d, fallback: %d.',
                $total,
                $llmCount,
                $fallbackCount
            ));
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

    /**
     * @param  array<string, mixed>  $question
     */
    private function batchSize(): int
    {
        $size = (int) config('services.openai.batch_size', 10);

        return max(1, min(60, $size));
    }

    private function makeProgressBar(int $total): ?ProgressBar
    {
        if (! $this->option('progress')) {
            return null;
        }

        $bar = new ProgressBar($this->errorOutput(), max(0, $total));
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');
        $bar->start();

        return $bar;
    }

    private function errorOutput(): OutputInterface
    {
        $output = $this->output;
        if ($output instanceof OutputStyle) {
            return $output->getErrorStyle();
        }

        return $output;
    }

    /**
     * @param  array<int, mixed>  $questions
     * @return array<int, array<string, mixed>>
     */
    private function validateQuestions(array $questions): array
    {
        $validated = [];
        $seenIds = [];

        foreach ($questions as $index => $question) {
            if (! is_array($question)) {
                throw new RuntimeException("Question at index {$index} must be an object.");
            }

            $id = $question['id'] ?? null;
            if (! is_string($id) || trim($id) === '') {
                throw new RuntimeException("Question at index {$index} has an invalid id.");
            }

            if (array_key_exists($id, $seenIds)) {
                throw new RuntimeException("Duplicate question id detected: {$id}.");
            }

            $seenIds[$id] = true;
            $validated[] = $question;
        }

        return $validated;
    }
}
