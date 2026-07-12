<?php
declare(strict_types=1);

namespace App\Presenters;

use Nette\Application\UI\Presenter;

abstract class BasePresenter extends Presenter
{
    protected function startup(): void
    {
        parent::startup();
        $this->template->currentUser = $this->getUser();
    }
}
