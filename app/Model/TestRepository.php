<?php
declare(strict_types=1);

namespace App\Model;

use Nette\Database\Connection;
use Nette\Database\Row;
use Nette\Database\ResultSet;

final class TestRepository
{
    public function __construct(private Connection $database) {}

    public function recentForUser(int $userId, int $limit = 10): ResultSet
    {
        return $this->database->query(
            'SELECT t.id, t.startDate, t.endDate, t.timeLimit, c.name AS categoryName,
                    COUNT(r.id) AS total,
                    SUM(CASE WHEN r.correct = 1 THEN 1 ELSE 0 END) AS correct
             FROM tests t
             JOIN category c ON c.id = t.category
             LEFT JOIN results r ON r.tests_id = t.id
             WHERE t.user_id = ? AND t.endDate IS NOT NULL
             GROUP BY t.id, t.startDate, t.endDate, t.timeLimit, c.name
             ORDER BY t.endDate DESC
             LIMIT ?',
            $userId,
            $limit,
        );
    }

    public function create(int $userId, int $timeLimit): int
    {
        $this->database->query(
            'INSERT INTO tests (user_id, category, timeLimit, startDate, endDate)
             VALUES (?, 3, ?, NOW(), NULL)',
            $userId,
            $timeLimit,
        );

        return (int) $this->database->getInsertId();
    }

    public function findForUser(int $testId, int $userId): ?Row
    {
        return $this->database->fetch(
            'SELECT * FROM tests WHERE id = ? AND user_id = ?',
            $testId,
            $userId,
        ) ?: null;
    }

    /**
     * @param array<int, int> $selectedAnswers questionId => answerId
     * @param array<int, int|null> $correctAnswers questionId => correctAnswerId
     */
    public function complete(int $testId, array $selectedAnswers, array $correctAnswers): void
    {
        $this->database->transaction(function () use ($testId, $selectedAnswers, $correctAnswers): void {
            $this->database->query('DELETE FROM results WHERE tests_id = ?', $testId);

            foreach ($selectedAnswers as $questionId => $answerId) {
                $isCorrect = isset($correctAnswers[$questionId])
                    && $correctAnswers[$questionId] !== null
                    && $correctAnswers[$questionId] === $answerId;

                $this->database->query(
                    'INSERT INTO results (tests_id, questions_id, answers_id, correct)
                     VALUES (?, ?, ?, ?)',
                    $testId,
                    $questionId,
                    $answerId,
                    $isCorrect ? 1 : null,
                );
            }

            $this->database->query('UPDATE tests SET endDate = NOW() WHERE id = ?', $testId);
        });
    }

    public function summary(int $testId, int $userId): ?Row
    {
        return $this->database->fetch(
            'SELECT t.id, t.startDate, t.endDate, t.timeLimit,
                    COUNT(r.id) AS total,
                    SUM(CASE WHEN r.correct = 1 THEN 1 ELSE 0 END) AS correct
             FROM tests t
             LEFT JOIN results r ON r.tests_id = t.id
             WHERE t.id = ? AND t.user_id = ?
             GROUP BY t.id, t.startDate, t.endDate, t.timeLimit',
            $testId,
            $userId,
        ) ?: null;
    }

    public function resultDetails(int $testId, int $userId): ResultSet
    {
        return $this->database->query(
            'SELECT q.id AS questionId, q.text AS questionText,
                    selected.text AS selectedText,
                    correct_answer.text AS correctText,
                    r.correct
             FROM results r
             JOIN tests t ON t.id = r.tests_id
             JOIN questions q ON q.id = r.questions_id
             JOIN answers selected ON selected.id = r.answers_id
             LEFT JOIN answers correct_answer
               ON correct_answer.questions_id = q.id AND correct_answer.correct = 1
             WHERE r.tests_id = ? AND t.user_id = ?
             ORDER BY r.id ASC',
            $testId,
            $userId,
        );
    }
}
