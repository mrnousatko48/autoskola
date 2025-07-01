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
            if ($this->course->by_agreement || empty($this->course->start_date) || empty($this->course->start_time)) {
                $this->template->formattedStart = 'dle domluvy';
            } else {
                // Handle start_date as a string (e.g., '2025-07-01')
                $startDate = $this->course->start_date;
                if (!empty($startDate) && is_string($startDate)) {
                    $startDate = date('d.m.Y', strtotime($startDate));
                } else {
                    $startDate = 'Není zadán datum';
                }

                // Handle start_time as a string (e.g., '19:54:00')
                $startTime = $this->course->start_time;
                if (!empty($startTime) && is_string($startTime)) {
                    $startTime = substr($startTime, 0, 5); // Extract HH:MM
                } else {
                    $startTime = 'Není zadán čas';
                }

                $this->template->formattedStart = "$startDate od {$startTime}h";
            }
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

    public function renderGallery(): void
    {
        $this->template->galleryImages = $this->pageFacade->getGalleryImages();
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

        $form->addSubmit('submit', 'Rezervovat se')
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

            // Format start date and time for the email
            if ($this->course->by_agreement || empty($this->course->start_date) || empty($this->course->start_time)) {
                $courseStartDate = 'dle domluvy';
            } else {
                $courseStartDate = 'Není zadán datum';
                if (!empty($this->course->start_date) && is_string($this->course->start_date)) {
                    $courseStartDate = date('d.m.Y', strtotime($this->course->start_date));
                }
                $startTime = 'Není zadán čas';
                if (!empty($this->course->start_time) && is_string($this->course->start_time)) {
                    $startTime = substr($this->course->start_time, 0, 5); // Extract HH:MM
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
                date('d.m.Y H:i'), // Current date and time: 01.07.2025 21:07
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