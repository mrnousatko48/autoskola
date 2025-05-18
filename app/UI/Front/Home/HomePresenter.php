<?php
declare(strict_types=1);

namespace App\UI\Front\Home;

use Nette;
use App\Model\PageFacade;
use App\Model\EmailFacade;
use Nette\Application\UI\Form;
use App\MailSender\MailSender;

final class HomePresenter extends Nette\Application\UI\Presenter
{
    private PageFacade $pageFacade;
    private EmailFacade $EmailFacade;
    private $course;
    private MailSender $mailSender;

    public function __construct(PageFacade $pageFacade, EmailFacade $EmailFacade, MailSender $mailSender)
    {
        $this->pageFacade = $pageFacade;
        $this->EmailFacade = $EmailFacade;
        $this->mailSender = $mailSender;
    }

    public function beforeRender(): void
    {
        parent::beforeRender();

        // Format start date and time for the course detail view
        if ($this->course) {
            // Handle start_date
            $startDate = $this->course->start_date instanceof \DateTimeInterface
                ? $this->course->start_date->format('d.m.Y')
                : $this->course->start_date;

            // Handle start_time, accounting for possible DateInterval or other types
            $startTime = $this->course->start_time;
            if ($startTime instanceof \DateInterval) {
                // Convert DateInterval to HH:MM (e.g., PT2H30M -> "02:30")
                $hours = $startTime->h;
                $minutes = $startTime->i;
                $startTime = sprintf('%02d:%02d', $hours, $minutes);
            } elseif (is_string($startTime)) {
                // Trim to HH:MM if it’s a string like "14:30:00"
                $startTime = substr($startTime, 0, 5);
            } else {
                // Fallback if unexpected type
                $startTime = 'Není zadán čas';
            }

            $this->template->formattedStart = "$startDate od {$startTime}h";
        }
    }

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

    public function renderCenik(): void
    {
        $this->template->groupedPrices = $this->pageFacade->getGroupedCoursePrices();
    }

    public function actionDetail(int $courseId): void
    {
        $this->course = $this->pageFacade->getCourseById($courseId);
    }

    public function renderDetail(int $courseId): void
    {
        $this->template->course = $this->course;
    }

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
            $registrationData = [
                'name'      => $data->name,
                'address'   => $data->address,
                'email'     => $data->email,
                'phone'     => $data->phone,
                'course_id' => $this->course->id,
            ];

            $this->EmailFacade->createRegistration($registrationData);

            $courseStartDate = 'Není zadán datum';
            if ($this->course->start_date instanceof \DateTimeInterface) {
                $courseStartDate = $this->course->start_date->format('d.m.Y');
                // Handle start_time for email
                $startTime = $this->course->start_time;
                if ($startTime instanceof \DateInterval) {
                    $startTime = sprintf('%02d:%02d', $startTime->h, $startTime->i);
                } elseif (is_string($startTime)) {
                    $startTime = substr($startTime, 0, 5);
                }
                $courseStartDate .= " od {$startTime}h";
            }

            $this->mailSender->sendRegistrationEmail(
                $data->email,
                $data->name,
                $this->course->name,
                $data->address,
                $this->course->location,
                $data->phone,
                date('d.m.Y H:i'),
                $courseStartDate
            );

            $this->flashMessage('Registrace byla úspěšně odeslána🚗', 'success');
            $this->redirect('this');
        } catch (\Exception $e) {
            if ($e instanceof \Nette\Application\AbortException) {
                throw $e;
            }
            $this->flashMessage('Registrace se nepodařila. Zkuste to prosím znovu.', 'danger');
            bdump($e->getMessage());
        }
    }
}