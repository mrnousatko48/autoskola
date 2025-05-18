<?php
declare(strict_types=1);

namespace App\UI\Admin\Register;

use Nette\Application\UI\Presenter;
use App\Model\PageFacade;
use App\Model\EmailFacade;

/**
 * RegisterPresenter provides admin pages for managing registrations.
 */
final class RegisterPresenter extends Presenter
{
    private PageFacade $pageFacade;
    private EmailFacade $emailFacade;

    public function __construct(PageFacade $pageFacade, EmailFacade $emailFacade)
    {
        parent::__construct();
        $this->pageFacade = $pageFacade;
        $this->emailFacade = $emailFacade;
    }

    protected function startup(): void
    {
        parent::startup();
        if (!$this->user->isLoggedIn()) {
            $this->flashMessage('Sem nemáš přístup🚫', 'danger');
            $this->redirect(':Front:Home:default');
        }
    }

    public function renderDefault(): void
    {
        // Get all courses
        $courses = $this->pageFacade->getAllCourses();
        
        // Get all registrations
        $registrations = $this->emailFacade->getAllRegistrations();
        
        // Group registrations by course_id and calculate counts
        $registrationsByCourse = [];
        $courseCounts = [];
        foreach ($registrations as $registration) {
            $courseId = $registration->course_id;
            if (!isset($registrationsByCourse[$courseId])) {
                $registrationsByCourse[$courseId] = [];
                $courseCounts[$courseId] = ['total' => 0, 'unaccepted' => 0];
            }
            $registrationsByCourse[$courseId][] = $registration;
            $courseCounts[$courseId]['total']++;
            if (!$registration->accepted) {
                $courseCounts[$courseId]['unaccepted']++;
            }
        }

        // Pass data to template
        $this->template->courses = $courses;
        $this->template->registrationsByCourse = $registrationsByCourse;
        $this->template->courseCounts = $courseCounts;
    }

    public function actionAccept(int $registrationId): void
    {
        try {
            $this->emailFacade->acceptRegistration($registrationId);
            $this->flashMessage('Uživatel byl přijat.', 'success');
        } catch (\Exception $e) {
            $this->flashMessage('Chyba při přijímání uživatele.', 'danger');
        }
        $this->redirect('Register:default');
    }

    public function actionDelete(int $registrationId): void
    {
        try {
            $this->emailFacade->deleteRegistration($registrationId);
            $this->flashMessage('Uživatel byl odstraněn.', 'success');
        } catch (\Exception $e) {
            $this->flashMessage('Chyba při odstraňování uživatele.', 'danger');
        }
        $this->redirect('Register:default');
    }
}