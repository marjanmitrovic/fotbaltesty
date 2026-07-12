<?php
declare(strict_types=1);

namespace App\Presenters;

use App\Model\QuestionRepository;
use App\Model\TestRepository;

final class DashboardPresenter extends BasePresenter
{
    public function __construct(
        private QuestionRepository $questions,
        private TestRepository $tests,
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
        $this->template->recentTests = $this->tests->recentForUser((int) $this->getUser()->getId());
    }
}
