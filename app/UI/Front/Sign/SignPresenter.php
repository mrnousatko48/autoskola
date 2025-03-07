<?php

declare(strict_types=1);

namespace App\UI\Front\Sign;

use App\Model\DuplicateNameException;
use App\Model\UserFacade;
use App\UI\Accessory\FormFactory;
use Nette;
use Nette\Application\Attributes\Persistent;
use Nette\Application\UI\Form;


/**
 * Presenter for sign-in and sign-up actions.
 */
final class SignPresenter extends Nette\Application\UI\Presenter
{
	/**
	 * Stores the previous page hash to redirect back after successful login.
	 */
	#[Persistent]
	public string $backlink = '';


	// Dependency injection of form factory and user management facade
	public function __construct(
		private UserFacade $userFacade,
		private FormFactory $formFactory,
	) {
	}


	/**
	 * Create a sign-in form with fields for username and password.
	 * On successful submission, the user is redirected to the dashboard or back to the previous page.
	 */
	protected function createComponentSignInForm(): Form
	{
		$form = $this->formFactory->create();
		$form->addText('username', 'Username:')
			 ->setRequired('Please enter your username.');
		$form->addPassword('password', 'Password:')
			 ->setRequired('Please enter your password.');
		$form->addSubmit('send', 'Přihlásit se');
	
		// Handle form submission
		$form->onSuccess[] = function (Form $form, \stdClass $data): void {
			try {
				// Attempt to login user
				$this->getUser()->login($data->username, $data->password);
				// Redirect to :Front:Home:default upon successful login
				$this->redirect(':Front:Home:default');
			} catch (\Exception $e) {
				$form->addError('Invalid credentials.');
			}
		};
	
		return $form;
	}

	



	/**
	 * Create a sign-up form with fields for username, email, and password.
	 * On successful submission, the user is redirected to the dashboard.
	 */
	protected function createComponentSignUpForm(): Form
	{
		$form = $this->formFactory->create();
	
		// Username field
		$form->addText('username', 'Username:')
			->setRequired('Please pick a username.');

		// Password field
		$form->addPassword('password', 'Password')
			->setOption('description', sprintf('At least %d characters', $this->userFacade::PasswordMinLength))
			->setRequired('Please create a password.')
			->addRule($form::MinLength, null, $this->userFacade::PasswordMinLength);
	
		// Confirm password field
		$form->addPassword('passwordConfirm', 'Confirm password')
			->setRequired('Please confirm your password.')
			->addRule($form::Equal, 'Passwords do not match.', $form['password']);
	
		// Submit button
		$form->addSubmit('send', 'Register');
	
		// Handle form submission
		$form->onSuccess[] = function (Form $form, \stdClass $data): void {
			try {
				if ($data->password !== $data->passwordConfirm) {
					$form['passwordConfirm']->addError('Passwords do not match.');
					return;
				}
	
				// Convert birth_date to a proper format (YYYY-MM-DD)
				$birthDateString = sprintf('%04d-%02d-%02d', $data->birth_year, $data->birth_month, $data->birth_day);
	
				// Default role for new user
				$role = 'uzivatel';
	
				// Default user image
				$image = '/www/uploads/default/user.png';
	
				// Register the new user
				$this->userFacade->add(
					$data->username,
					$data->name,
					$data->surname,
					$data->email,
					$data->phone,
					$data->password,
					$role,
					$image,
					$birthDateString, // Converted birth date
					$data->address
				);
	
				// Automatically log the user in using the username and plain password.
				$this->getUser()->login($data->username, $data->password);
	
				// Redirect after successful registration and login
				$this->redirect('Dashboard:default');
			} catch (DuplicateNameException $e) {
				$form['username']->addError('Username is already taken.');
			}
		};
	
		return $form;
	}
	
	


public function actionOut(): void
{
    $this->getUser()->logout(); // Odhlášení uživatele
    $this->redirect('Home:default'); // Přesměrování na domovskou stránku
}

public function actionIn(): void
{
    if ($this->getUser()->isLoggedIn()) {
        $session = $this->getSession()->getSection('repairForm');

        if ($session->values) {
            // Redirect back to the repair form after login
            $this->redirect('Repair:default', ['prefill' => $session->values]);
        }
    }
}


}