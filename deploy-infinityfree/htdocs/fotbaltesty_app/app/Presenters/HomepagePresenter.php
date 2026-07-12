<?php
declare(strict_types=1);

namespace App\Presenters;

use App\Model\QuestionRepository;

final class HomepagePresenter extends BasePresenter
{
    public function __construct(private QuestionRepository $questions)
    {
        parent::__construct();
    }

    public function renderDefault(): void
    {
        $this->template->questionCount = $this->questions->countActive();
    }
}
