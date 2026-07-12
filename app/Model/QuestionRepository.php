<?php
declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

final class QuestionRepository
{
    public function __construct(private Explorer $database) {}

    public function active(): Selection
    {
        return $this->database->table('questions')
            ->where('createDate <= ?', new \DateTimeImmutable())
            ->where('deactivated IS NULL OR deactivated = 0')
            ->order('id ASC');
    }

    public function countActive(): int
    {
        return $this->active()->count('*');
    }

    public function findById(int $id): ?ActiveRow
    {
        return $this->database->table('questions')->get($id) ?: null;
    }

    public function answersFor(int $questionId): Selection
    {
        return $this->database->table('answers')
            ->where('questions_id', $questionId)
            ->order('id ASC');
    }

    /** @return list<int> */
    public function randomActiveIds(int $limit): array
    {
        $rows = $this->database->query(
            'SELECT q.id
             FROM questions q
             WHERE q.createDate <= NOW()
               AND (q.deactivated IS NULL OR q.deactivated = 0)
               AND EXISTS (SELECT 1 FROM answers a WHERE a.questions_id = q.id)
             ORDER BY RAND()
             LIMIT ?',
            $limit,
        )->fetchAll();

        return array_values(array_map(static fn($row): int => (int) $row->id, $rows));
    }

    /**
     * @param list<int> $ids
     * @return array<int, array{question: ActiveRow, answers: array<int, string>, correctAnswerId: int|null}>
     */
    public function questionsWithAnswers(array $ids): array
    {
        $result = [];
        foreach ($ids as $id) {
            $question = $this->findById($id);
            if (!$question) {
                continue;
            }

            $answers = [];
            $correctAnswerId = null;
            foreach ($this->answersFor($id) as $answer) {
                $answerId = (int) $answer->id;
                $answers[$answerId] = (string) $answer->text;
                if ((bool) $answer->correct) {
                    $correctAnswerId = $answerId;
                }
            }

            if ($answers !== []) {
                $result[$id] = [
                    'question' => $question,
                    'answers' => $answers,
                    'correctAnswerId' => $correctAnswerId,
                ];
            }
        }

        return $result;
    }
}
