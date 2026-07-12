<?php
declare(strict_types=1);

namespace App\Presenters;

use App\Model\QuestionRepository;
use App\Model\TestRepository;
use Nette\Application\UI\Form;
use Nette\Http\Session;

final class TestPresenter extends BasePresenter
{
    public function __construct(
        private QuestionRepository $questions,
        private TestRepository $tests,
        private Session $session,
    ) {
        parent::__construct();
    }

    protected function startup(): void
    {
        parent::startup();
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
    }

    public function renderDefault(): void
    {
        $this->template->questionCount = $this->questions->countActive();
    }

    protected function createComponentSetupForm(): Form
    {
        $form = new Form();
        $form->addSelect('questionCount', 'Počet otázek', [
            5 => '5 otázek',
            10 => '10 otázek',
            20 => '20 otázek',
            30 => '30 otázek',
        ])->setDefaultValue(10);
        $form->addSelect('timeLimit', 'Časový limit', [
            10 => '10 minut',
            20 => '20 minut',
            30 => '30 minut',
            45 => '45 minut',
        ])->setDefaultValue(20);
        $form->addSubmit('start', 'Spustit test');
        $form->onSuccess[] = $this->setupFormSucceeded(...);
        return $form;
    }

    private function setupFormSucceeded(Form $form, \stdClass $values): void
    {
        $count = max(5, min(30, (int) $values->questionCount));
        $timeLimit = max(10, min(45, (int) $values->timeLimit));
        $questionIds = $this->questions->randomActiveIds($count);

        if (count($questionIds) < $count) {
            $form->addError('V databázi není dostatek aktivních otázek.');
            return;
        }

        $testId = $this->tests->create((int) $this->getUser()->getId(), $timeLimit);
        $section = $this->session->getSection('generatedTests');
        $section->set((string) $testId, $questionIds);
        $section->setExpiration('2 hours', (string) $testId);

        $this->redirect('Test:run', $testId);
    }

    public function actionRun(int $id): void
    {
        $test = $this->tests->findForUser($id, (int) $this->getUser()->getId());
        if (!$test || $test->endDate !== null) {
            $this->flashMessage('Test nebyl nalezen nebo již byl dokončen.', 'error');
            $this->redirect('Dashboard:default');
        }

        $questionIds = $this->getStoredQuestionIds($id);
        if ($questionIds === []) {
            $this->flashMessage('Platnost testu vypršela. Spusťte nový test.', 'error');
            $this->redirect('Test:default');
        }

        $this->template->test = $test;
        $this->template->questions = $this->questions->questionsWithAnswers($questionIds);
    }

    protected function createComponentAnswerForm(): Form
    {
        $testId = (int) ($this->getParameter('id') ?? 0);
        $questionIds = $this->getStoredQuestionIds($testId);
        $questionData = $this->questions->questionsWithAnswers($questionIds);

        $form = new Form();
        foreach ($questionData as $questionId => $data) {
            $form->addRadioList('question_' . $questionId, '', $data['answers'])
                ->setRequired('Vyberte jednu odpověď.');
        }
        $form->addSubmit('finish', 'Dokončit test');
        $form->onSuccess[] = $this->answerFormSucceeded(...);
        return $form;
    }

    private function answerFormSucceeded(Form $form, \stdClass $values): void
    {
        $testId = (int) ($this->getParameter('id') ?? 0);
        $test = $this->tests->findForUser($testId, (int) $this->getUser()->getId());
        if (!$test || $test->endDate !== null) {
            $this->flashMessage('Test již nelze dokončit.', 'error');
            $this->redirect('Dashboard:default');
        }

        $questionIds = $this->getStoredQuestionIds($testId);
        $questionData = $this->questions->questionsWithAnswers($questionIds);
        $selected = [];
        $correct = [];

        foreach ($questionData as $questionId => $data) {
            $field = 'question_' . $questionId;
            $answerId = (int) ($values->{$field} ?? 0);
            if (!isset($data['answers'][$answerId])) {
                $form->addError('Jedna z odpovědí není platná.');
                return;
            }
            $selected[$questionId] = $answerId;
            $correct[$questionId] = $data['correctAnswerId'];
        }

        $this->tests->complete($testId, $selected, $correct);
        $this->session->getSection('generatedTests')->remove((string) $testId);
        $this->redirect('Test:result', $testId);
    }

    public function renderResult(int $id): void
    {
        $userId = (int) $this->getUser()->getId();
        $summary = $this->tests->summary($id, $userId);
        if (!$summary || $summary->endDate === null) {
            $this->flashMessage('Výsledek testu nebyl nalezen.', 'error');
            $this->redirect('Dashboard:default');
        }

        $this->template->summary = $summary;
        $this->template->details = $this->tests->resultDetails($id, $userId);
        $this->template->percentage = (int) $summary->total > 0
            ? (int) round(((int) $summary->correct / (int) $summary->total) * 100)
            : 0;
    }

    /** @return list<int> */
    private function getStoredQuestionIds(int $testId): array
    {
        if ($testId <= 0) {
            return [];
        }

        $value = $this->session->getSection('generatedTests')->get((string) $testId);
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_map('intval', $value));
    }
}
