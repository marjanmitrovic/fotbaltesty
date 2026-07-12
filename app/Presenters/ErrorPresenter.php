<?php
declare(strict_types=1);

namespace App\Presenters;

use Nette\Application\BadRequestException;
use Nette\Application\UI\Presenter;

final class ErrorPresenter extends Presenter
{
    public function renderDefault(\Throwable $exception): void
    {
        $code = $exception instanceof BadRequestException ? $exception->getCode() : 500;
        $this->getHttpResponse()->setCode($code);
        $this->template->code = $code;
        $this->template->message = $code === 404 ? 'Stránka nebyla nalezena.' : 'Došlo k neočekávané chybě.';
    }
}
