<?php

declare(strict_types=1);

namespace App\UI\Front\Sign;

use App\Model\DuplicateNameException;
use App\Model\UserFacade;
use App\UI\Accessory\FormFactory;
use Nette;
use Nette\Application\UI\Form;

final class SignPresenter extends Nette\Application\UI\Presenter
{
    // Dependency injection of form factory and user management facade
    public function __construct(
        private UserFacade $userFacade,
        private FormFactory $formFactory
    ) {
        parent::__construct();
    }

    /**
     * Creates a sign-in form with only username and password fields.
     * Upon successful login, the user is redirected to Home:default.
     */
    protected function createComponentSignInForm(): Form
    {
        $form = $this->formFactory->create();
        $form->addText('username', 'Username:')
             ->setRequired('Please enter your username.');
        $form->addPassword('password', 'Password:')
             ->setRequired('Please enter your password.');
        $form->addSubmit('send', 'Přihlásit se');

        $form->onSuccess[] = function (Form $form, \stdClass $data): void {
            try {
                // Attempt to log in with provided credentials.
                $this->getUser()->login($data->username, $data->password);
                $this->redirect(':Front:Home:default');
            } catch (Nette\Security\AuthenticationException $e) {
                $form->addError('The username or password you entered is incorrect.');
            }
        };

        return $form;
    }

    /**
     * Creates a sign-up form with minimal fields: username, password, and password confirmation.
     * Upon successful registration, the user is logged in and redirected to Home:default.
     */
    protected function createComponentSignUpForm(): Form
    {
        $form = $this->formFactory->create();
        $form->addText('username', 'Username:')
             ->setRequired('Please choose a username.');
        $form->addPassword('password', 'Password:')
             ->setRequired('Please enter a password.');
        $form->addPassword('passwordConfirm', 'Confirm Password:')
             ->setRequired('Please confirm your password.')
             ->addRule($form::EQUAL, 'Passwords do not match.', $form['password']);
        $form->addSubmit('send', 'Register');

        $form->onSuccess[] = function (Form $form, \stdClass $data): void {
            try {
                if ($data->password !== $data->passwordConfirm) {
                    $form['passwordConfirm']->addError('Passwords do not match.');
                    return;
                }
                // Set default role
                $role = 'uzivatel';
                // Register the new user with only username and password.
                $this->userFacade->add($data->username, $data->password, $role);
                // Automatically log the new user in.
                $this->getUser()->login($data->username, $data->password);
                $this->redirect(':Front:Home:default');
            } catch (DuplicateNameException $e) {
                $form['username']->addError('Username is already taken.');
            }
        };

        return $form;
    }

    /**
     * Logs out the user and redirects to Home:default.
     */
    public function actionOut(): void
    {
        $this->getUser()->logout();
        $this->redirect(':Front:Home:default');
    }
}
