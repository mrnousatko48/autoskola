<?php
declare(strict_types=1);

namespace App\UI\Admin\Dashboard;

use Nette\Application\UI\Presenter;
use Nette\Application\UI\Form;
use App\Model\PageFacade;

/**
 * DashboardPresenter provides separate admin pages for editing each section.
 */
final class DashboardPresenter extends Presenter
{
    /** @var PageFacade */
    private PageFacade $pageFacade;

    /**
     * Constructor.
     *
     * @param PageFacade $pageFacade Central facade for retrieving and updating page content.
     */
    public function __construct(PageFacade $pageFacade)
    {
        parent::__construct();
        $this->pageFacade = $pageFacade;
    }

    /**
     * Default dashboard view with navigation buttons.
     */
    public function renderDefault(): void
    {
        // No additional data is needed for the dashboard menu.
    }

    /**
     * Render the Hero Section edit page.
     */
    public function renderHero(): void
    {
        $this->template->hero = $this->pageFacade->getHeroSection();
        $this->template->setFile(__DIR__ . '/Templates/hero.latte');
    }

    /**
     * Render the About Section edit page.
     */
    public function renderAbout(): void
    {
        $this->template->about = $this->pageFacade->getAboutSection();
        $this->template->setFile(__DIR__ . '/Templates/about.latte');
    }

    /**
     * Render the Contact Section edit page.
     */
    public function renderContact(): void
    {
        $this->template->contact = $this->pageFacade->getContactInfo();
        $this->template->setFile(__DIR__ . '/Templates/contact.latte');
    }

    /**
     * Render the Advantages edit page.
     *
     * If an 'id' parameter is passed, load that advantage for editing;
     * otherwise, load the list of advantages.
     */
    public function renderAdvantages(): void
    {
        $id = $this->getParameter('id');
        if ($id) {
            $advantage = $this->pageFacade->getAdvantages()->get($id);
            if (!$advantage) {
                $this->error('Advantage not found');
            }
            $this->template->advantage = $advantage;
        } else {
            $this->template->advantages = $this->pageFacade->getAdvantages();
        }
        $this->template->setFile(__DIR__ . '/Templates/advantages.latte');
    }

    /**
     * Render the Offerings edit page.
     */
    public function renderOfferings(): void
    {
        $this->template->offerings = $this->pageFacade->getOfferings();
        $this->template->setFile(__DIR__ . '/Templates/offerings.latte');
    }

    /**
     * Render the Course Prices ("Ceník") edit page.
     */
    public function renderPrices(): void
    {
        $this->template->groupedPrices = $this->pageFacade->getGroupedCoursePrices();
        $this->template->setFile(__DIR__ . '/Templates/prices.latte');
    }

    /**
     * Render the Courses edit page.
     */
    public function renderCourses(): void
    {
        $this->template->courses = $this->pageFacade->getAllCourses();
        $this->template->setFile(__DIR__ . '/Templates/courses.latte');
    }

    /* ------------------- Form Components ------------------- */

    /**
     * Helper method to build an edit form.
     *
     * @param object   $entity              The entity providing default values.
     * @param array    $fields              Array of field definitions.
     *                                    Example:
     *                                    [
     *                                      'heading' => ['type' => 'text', 'label' => 'Heading:', 'required' => true],
     *                                      'subheading'  => ['type' => 'textArea', 'label' => 'Subheading:', 'required' => true],
     *                                    ]
     * @param callable $updateCallback      Callback to update the entity: fn($id, $values)
     * @param string   $flashMessage        The flash message on success.
     * @param string   $redirectDestination The redirect destination.
     * @return Form
     */
    private function createEditForm(
        object $entity,
        array $fields,
        callable $updateCallback,
        string $flashMessage,
        string $redirectDestination
    ): Form {
        $form = new Form;
        foreach ($fields as $name => $config) {
            $method = 'add' . ucfirst($config['type']);
            $field = $form->$method($name, $config['label'] ?? '');
            // Use a provided 'default' value or fallback to the entity property.
            if (array_key_exists('default', $config)) {
                $field->setDefaultValue($config['default']);
            } else {
                $field->setDefaultValue($entity->{$name});
            }
            if (!empty($config['required'])) {
                $field->setRequired();
            }
        }
        $form->addSubmit('save', 'Save');

        $form->onSuccess[] = function (Form $form, $values) use ($entity, $updateCallback, $flashMessage, $redirectDestination): void {
            $updateCallback($entity->id, (array)$values);
            $this->flashMessage($flashMessage, 'success');
            $this->redirect($redirectDestination);
        };

        return $form;
    }

    /**
     * Create a form to edit the Hero Section.
     *
     * @return Form
     */
    public function createComponentHeroForm(): Form
    {
        $hero = $this->pageFacade->getHeroSection();
        $fields = [
            'heading'     => ['type' => 'text', 'label' => 'Heading:', 'required' => true],
            'subheading'  => ['type' => 'textArea', 'label' => 'Subheading:', 'required' => true],
            'button_text' => ['type' => 'text', 'label' => 'Button Text:', 'required' => true],
            'button_link' => ['type' => 'text', 'label' => 'Button Link:', 'required' => true],
        ];

        return $this->createEditForm(
            $hero,
            $fields,
            fn($id, $values) => $this->pageFacade->updateHeroSection($id, $values),
            'Hero section updated successfully.',
            'Dashboard:hero'
        );
    }

    /**
     * Create a form to edit the About Section.
     *
     * @return Form
     */
    public function createComponentAboutForm(): Form
    {
        $about = $this->pageFacade->getAboutSection();
        $fields = [
            'heading'  => ['type' => 'text', 'label' => 'Heading:', 'required' => true],
            'image'    => ['type' => 'text', 'label' => 'Image URL:', 'required' => true],
            'alt_text' => ['type' => 'text', 'label' => 'Alt Text:', 'required' => true],
            'content'  => ['type' => 'textArea', 'label' => 'Content:', 'required' => true],
        ];

        return $this->createEditForm(
            $about,
            $fields,
            fn($id, $values) => $this->pageFacade->updateAboutSection($id, $values),
            'About section updated successfully.',
            'Dashboard:about'
        );
    }

    /**
     * Create a form to edit the Contact Section.
     *
     * @return Form
     */
    public function createComponentContactForm(): Form
    {
        $contact = $this->pageFacade->getContactInfo();
        $fields = [
            'name'      => ['type' => 'text', 'label' => 'Name:', 'required' => true],
            'address'   => ['type' => 'text', 'label' => 'Address:', 'required' => true],
            'ico'       => ['type' => 'text', 'label' => 'IČO:', 'required' => true],
            'phone'     => ['type' => 'text', 'label' => 'Phone:', 'required' => true],
            'email'     => ['type' => 'text', 'label' => 'Email:', 'required' => true],
            'map_embed' => ['type' => 'textArea', 'label' => 'Map Embed Code:', 'required' => true],
        ];

        return $this->createEditForm(
            $contact,
            $fields,
            fn($id, $values) => $this->pageFacade->updateContactInfo($id, $values),
            'Contact information updated successfully.',
            'Dashboard:contact'
        );
    }

    /**
     * Create a form to edit an Advantage.
     *
     * When an 'id' parameter is provided (via the URL), the form edits that specific advantage.
     *
     * @return Form
     */
    public function createComponentAdvantageForm(): Form
    {
        $id = (int)$this->getParameter('id');
        $advantage = $this->pageFacade->getAdvantages()->get($id);
        if (!$advantage) {
            $this->error('Advantage not found');
        }
        $fields = [
            'icon'        => ['type' => 'text', 'label' => 'Icon:', 'required' => true],
            'title'       => ['type' => 'text', 'label' => 'Title:', 'required' => true],
            'description' => ['type' => 'textArea', 'label' => 'Description:', 'required' => true],
            'ordering'    => ['type' => 'integer', 'label' => 'Ordering:', 'required' => true],
        ];

        return $this->createEditForm(
            $advantage,
            $fields,
            fn($id, $values) => $this->pageFacade->updateAdvantage($id, $values),
            'Advantage updated successfully.',
            'Dashboard:advantages'
        );
    }

    /**
     * Create a form component to edit a single Price row.
     *
     * The form loads a price by its id (passed as a URL parameter) and updates it on submission.
     *
     * @return Form
     */
    public function createComponentPriceForm(): Form
    {
        $id = (int)$this->getParameter('id');
        $price = $this->pageFacade->getPriceById($id);
        if (!$price) {
            $this->error('Price not found');
        }
        $fields = [
            'item'        => ['type' => 'text', 'label' => 'Item:', 'required' => true],
            'price'       => ['type' => 'text', 'label' => 'Price:', 'required' => true],
            'description' => ['type' => 'textArea', 'label' => 'Description:'],
            'ordering'    => ['type' => 'integer', 'label' => 'Ordering:', 'required' => true],
            'section'     => ['type' => 'hidden', 'label' => ''],
        ];

        return $this->createEditForm(
            $price,
            $fields,
            fn($id, $values) => $this->pageFacade->updatePrice($id, $values),
            'Price updated successfully.',
            'Dashboard:prices'
        );
    }

    /**
     * Create a form to edit a Course.
     *
     * @return Form
     */
    public function createComponentCourseForm(): Form
    {
        $id = (int)$this->getParameter('id');
        $course = $this->pageFacade->getCourseById($id);
        if (!$course) {
            $this->error('Course not found');
        }
        $fields = [
            'name'        => ['type' => 'text', 'label' => 'Course Name:', 'required' => true],
            'description' => ['type' => 'textArea', 'label' => 'Description:', 'required' => true],
            'image'       => ['type' => 'text', 'label' => 'Image URL:', 'required' => true],
        ];

        return $this->createEditForm(
            $course,
            $fields,
            fn($id, $values) => $this->pageFacade->updateCourse($id, $values),
            'Course updated successfully.',
            'Dashboard:courses'
        );
    }
}