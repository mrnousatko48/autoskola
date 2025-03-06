<?php
// filepath: /root/autoskola/app/UI/Front/Home/HomePresenter.php

declare(strict_types=1);

namespace App\UI\Front\Home;

use Nette;
use App\Model\CourseFacade;

final class HomePresenter extends Nette\Application\UI\Presenter
{
    private CourseFacade $courseFacade;

    public function __construct(CourseFacade $courseFacade)
    {
        parent::__construct();
        $this->courseFacade = $courseFacade;
    }

    public function renderDefault(): void
    {
        $this->template->courses = $this->courseFacade->getAllCourses();
        $this->template->groupedPrices = $this->courseFacade->getGroupedCoursePrices();
    }

    // If you need a separate action for "cenik", you can fix its typo:
    public function renderCenik(): void
    {
        $this->template->groupedPrices = $this->courseFacade->getGroupedCoursePrices();
    }
}