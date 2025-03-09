<?php
declare(strict_types=1);

namespace App\UI\Front\Home;

use Nette;
use App\Model\PageFacade;
use App\Model\RegistrationFacade;
use Nette\Application\UI\Form;
use App\MailSender\MailSender;

final class HomePresenter extends Nette\Application\UI\Presenter
{
    /** @var PageFacade */
    private PageFacade $pageFacade;
    private RegistrationFacade $registrationFacade;
    private $course;
    private MailSender $mailSender;

    /**
     * Constructor.
     *
     * @param PageFacade $pageFacade Central facade for retrieving page content from multiple tables.
     */
    public function __construct(PageFacade $pageFacade, RegistrationFacade $registrationFacade, MailSender $mailSender)
    {
        $this->pageFacade = $pageFacade;
        $this->registrationFacade = $registrationFacade;
        $this->mailSender = $mailSender;
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
        $this->template->groupedPrices = $this->pageFacade->getGroupedCoursePrices();
    }

    public function actionDetail(int $courseId): void
    {
        $this->course = $this->pageFacade->getCourseById($courseId);
    }

    public function renderDetail(int $courseId)
    {
        $this->template->course = $this->course;
    }

    // Create the registration form
    protected function createComponentRegistrationForm()
    {
        $form = new Form();

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

        $form->onSuccess[] = [$this, 'registrationFormSucceded'];

        return $form;
    }

    public function registrationFormSucceded(Form $form, $data)
{
    try {
        // Příprava dat pro vložení do databáze
        $registrationData = [
            'name'      => $data->name,
            'address'   => $data->address,
            'email'     => $data->email,
            'phone'     => $data->phone,
            'course_id' => $this->course->id,
        ];

        // Vložení registrace do databáze
        $this->registrationFacade->createRegistration($registrationData);

        // Odeslání notifikačního emailu administrátorovi
        $this->mailSender->sendRegistrationEmail(
            'burdadko.cz@gmail.com',
            $data->email,
            $data->name,
            $this->course->name,
            $data->address,
            $data->phone,
            date('d.m.Y H:i')
        );

        // Zpráva o úspěchu a přesměrování
        $this->flashMessage('Registrace byla úspěšně odeslána a email byl odeslán administrátorovi!', 'success');
        $this->redirect('this');
    } catch (\Exception $e) {
        // Pokud je výjimka typu AbortException, přehodíme ji, aby ji zpracoval Nette
        if ($e instanceof \Nette\Application\AbortException) {
            throw $e;
        }
        // Zachycení ostatních výjimek (např. databázových nebo emailových chyb)
        $this->flashMessage('Registrace se nepodařila. Zkuste to prosím znovu.', 'danger');
        bdump($e->getMessage()); // Logování chyby pro ladění
    }
}
}