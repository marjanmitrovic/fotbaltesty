<?php
declare(strict_types=1);

namespace App\Presenters;

use Nette\Application\UI\Form;
use Nette\Security\AuthenticationException;

final class SignPresenter extends BasePresenter
{
    public function actionIn(): void
    {
        if ($this->getUser()->isLoggedIn()) {
            $this->redirect('Dashboard:default');
        }
    }

    protected function createComponentSignInForm(): Form
    {
        $form = new Form();
        $form->addEmail('email', 'E-mail')->setRequired('Zadejte e-mail.');
        $form->addPassword('password', 'Heslo')->setRequired('Zadejte heslo.');
        $form->addCheckbox('remember', 'Pamatovat přihlášení');
        $form->addSubmit('send', 'Přihlásit se');
        $form->onSuccess[] = function (Form $form, \stdClass $values): void {
            try {
                $this->getUser()->setExpiration($values->remember ? '14 days' : '30 minutes');
                $this->getUser()->login($values->email, $values->password);
                $this->redirect('Dashboard:default');
            } catch (AuthenticationException $e) {
                $form->addError($e->getMessage());
            }
        };
        return $form;
    }

    public function actionOut(): void
    {
        $this->getUser()->logout(true);
        $this->redirect('Homepage:default');
    }
}
