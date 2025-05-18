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
    private int $itemsPerPage = 5; // Number of registrations per page

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
        $paginatedRegistrations = [];
        $paginationInfo = [];

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

        // Paginate registrations for each course
        foreach ($courses as $course) {
            $courseId = $course->id;
            // Get current page from query parameter (e.g., page-1 for course ID 1)
            $currentPage = (int) $this->getParameter("page-$courseId", 1);
            if ($currentPage < 1) {
                $currentPage = 1;
            }

            // Get registrations for this course
            $courseRegistrations = $registrationsByCourse[$courseId] ?? [];
            $totalItems = count($courseRegistrations);
            $totalPages = max(1, ceil($totalItems / $this->itemsPerPage));

            // Ensure current page is within bounds
            if ($currentPage > $totalPages) {
                $currentPage = $totalPages;
            }

            // Slice registrations for the current page
            $offset = ($currentPage - 1) * $this->itemsPerPage;
            $paginatedRegistrations[$courseId] = array_slice($courseRegistrations, $offset, $this->itemsPerPage);

            // Store pagination info
            $paginationInfo[$courseId] = [
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'totalItems' => $totalItems,
            ];
        }

        // Pass data to template
        $this->template->courses = $courses;
        $this->template->registrationsByCourse = $paginatedRegistrations;
        $this->template->courseCounts = $courseCounts;
        $this->template->paginationInfo = $paginationInfo;
    }

    public function actionAccept(int $registrationId): void
    {
        try {
            $this->emailFacade->acceptRegistration($registrationId);
            $this->flashMessage('Uživatel byl přijat.', 'success');
        } catch (\Exception $e) {
            $this->flashMessage('Chyba při přijímání uživatele.', 'danger');
        }
        $this->redirect('this');
    }

    public function actionDelete(int $registrationId): void
    {
        try {
            $this->emailFacade->deleteRegistration($registrationId);
            $this->flashMessage('Uživatel byl odstraněn.', 'success');
        } catch (\Exception $e) {
            $this->flashMessage('Chyba při odstraňování uživatele.', 'danger');
        }
        $this->redirect('this');
    }
}