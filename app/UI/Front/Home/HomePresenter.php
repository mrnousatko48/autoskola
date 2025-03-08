<?php
declare(strict_types=1);

namespace App\UI\Front\Home;

use Nette;
use App\Model\PageFacade;
use App\Model\RegistrationFacade;
use Nette\Application\UI\Form;

final class HomePresenter extends Nette\Application\UI\Presenter
{
    /** @var PageFacade */
    private PageFacade $pageFacade;
    private RegistrationFacade $registrationFacade;
    private $course;

    /**
     * Constructor.
     *
     * @param PageFacade $pageFacade Central facade for retrieving page content from multiple tables.
     */
    public function __construct(PageFacade $pageFacade, RegistrationFacade $registrationFacade)
    {
        parent::__construct();
        $this->pageFacade = $pageFacade;
        $this->registrationFacade = $registrationFacade;
    }

    /**
     * Render the default homepage.
     *
     * Loads the hero, about, advantages, offerings, contact, course prices, and courses data.
     */
    public function renderDefault(): void
    {

        $this->template->hero = $this->pageFacade->getHeroSection();
        $this->template->about = $this->pageFacade->getAboutSection();
        $this->template->advantages = $this->pageFacade->getAdvantages();
        $this->template->offers = $this->pageFacade->getOfferings();
        $this->template->contact = $this->pageFacade->getContactInfo();
        $this->template->groupedPrices = $this->pageFacade->getGroupedCoursePrices();
        $this->template->courses = $this->pageFacade->getAllCourses();
    }

    /**
     * Render the course pricing ("Ceník") page.
     */
    public function renderCenik(): void
    {
        // Only the grouped course prices are needed here.
        $this->template->groupedPrices = $this->pageFacade->getGroupedCoursePrices();
    }

    public function actionDetail(int $courseId): void
    {
        // Retrieve the course via the facade
        $this->course = $this->pageFacade->getCourseById($courseId);
    }

    public function renderDetail(int $courseId)
    {
        $this->template->course = $this->course;
    }

    // Create the registration form
    protected function createComponentRegistrationForm(): Form
    {
        $form = new Form;

        $form->addText('name', 'Jméno:')
            ->setRequired('Prosím vyplňte své jméno.')
            ->setHtmlAttribute('class', 'form-control');

        $form->addText('address', 'Adresa:')
            ->setRequired('Prosím vyplňte svou adresu.')
            ->setHtmlAttribute('class', 'form-control');

        $form->addEmail('email', 'E-mail:')
            ->setRequired('Prosím vyplňte svůj e-mail.')
            ->setHtmlAttribute('class', 'form-control');

        $form->addText('phone', 'Telefon:')
            ->setRequired('Prosím vyplňte své telefonní číslo.')
            ->addRule(Form::PATTERN, 'Telefonní číslo musí být ve správném formátu.', '^[0-9\s+]*$')
            ->setHtmlAttribute('class', 'form-control');

        $form->addSubmit('submit', 'Zaregistrovat se')
            ->setHtmlAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = [$this, 'registrationFormSucceeded'];

        return $form;
    }

    // Handle form submission
    public function registrationFormSucceeded(Form $form, \stdClass $values)
    {
        try {
            $data = [
                'name'      => $values->name,
                'address'   => $values->address,
                'email'     => $values->email,
                'phone'     => $values->phone,
                'course_id' => $this->course->id, // Link to the current course
            ];
    
            $this->registrationFacade->createRegistration($data);
            $this->flashMessage('Registrace byla úspěšně odeslána!', 'success');
            $this->redirect('this');
        } 
        catch (\Nette\Application\AbortException $e) {
            throw $e; // Let Nette handle the abort exception.
        }
        catch (\Exception $e) {
            // Optional: if you want to catch duplicate entry errors (e.g., code '23000')
            if ($e->getCode() === '23000') {
                $this->flashMessage('Jste již registrován!', 'danger');
            } else {
                $this->flashMessage('Registrace se nepodařilo odeslat. Zkuste to prosím znovu.', 'danger');
            }
        }
    }
}
