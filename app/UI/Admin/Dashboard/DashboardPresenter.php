<?php

declare(strict_types=1);

namespace App\UI\Admin\Dashboard;

use Nette\Application\UI\Presenter;
use Nette\Application\UI\Form;
use App\Model\PageFacade;

final class DashboardPresenter extends Presenter
{
    /** @var PageFacade */
    private PageFacade $pageFacade;

    /**
     * Constructor.
     *
     * @param PageFacade $pageFacade Central facade to retrieve and update page content.
     */
    public function __construct(PageFacade $pageFacade)
    {
        parent::__construct();
        $this->pageFacade = $pageFacade;
    }

    /**
     * Render the dashboard.
     *
     * This method loads all the content from the database and passes it to the template.
     */
    public function renderDefault(): void
    {
        // Retrieve content from all page sections.
        $this->template->hero = $this->pageFacade->getHeroSection();
        $this->template->about = $this->pageFacade->getAboutSection();
        $this->template->advantages = $this->pageFacade->getAdvantages();
        $this->template->offerings = $this->pageFacade->getOfferings();
        $this->template->contact = $this->pageFacade->getContactInfo();
        $this->template->groupedPrices = $this->pageFacade->getGroupedCoursePrices();
        // You can add more content (e.g. courses) as needed.
    }

    /**
     * Create a form component to edit the Hero Section.
     *
     * This form pre-fills values from the hero section record and, on submission,
     * calls PageFacade::updateHeroSection() to save changes.
     *
     * @return Form
     */
    public function createComponentHeroForm(): Form
    {
        $form = new Form;
        // Retrieve current hero section data.
        $hero = $this->pageFacade->getHeroSection();

        // Add form fields with default values.
        $form->addText('heading', 'Heading:')
             ->setDefaultValue($hero->heading)
             ->setRequired();
        $form->addTextArea('subheading', 'Subheading:')
             ->setDefaultValue($hero->subheading)
             ->setRequired();
        $form->addText('button_text', 'Button Text:')
             ->setDefaultValue($hero->button_text)
             ->setRequired();
        $form->addText('button_link', 'Button Link:')
             ->setDefaultValue($hero->button_link)
             ->setRequired();
        $form->addSubmit('save', 'Save');

        // On form success, update the hero section record.
        $form->onSuccess[] = function (Form $form, $values) use ($hero): void {
            // Update method in PageFacade must be implemented accordingly.
            $this->pageFacade->updateHeroSection($hero->id, (array)$values);
            $this->flashMessage('Hero section updated successfully.', 'success');
            $this->redirect('this');
        };

        return $form;
    }

        /* ------------------- About Section Edit Form ------------------- */

    /**
     * Create a form to edit the About section.
     *
     * @return Form
     */
    public function createComponentAboutForm(): Form
    {
        $form = new Form;
        $about = $this->pageFacade->getAboutSection();

        $form->addText('heading', 'Heading:')
             ->setDefaultValue($about->heading)
             ->setRequired();
        $form->addText('image', 'Image URL:')
             ->setDefaultValue($about->image)
             ->setRequired();
        $form->addText('alt_text', 'Alt Text:')
             ->setDefaultValue($about->alt_text)
             ->setRequired();
        $form->addTextArea('content', 'Content:')
             ->setDefaultValue($about->content)
             ->setRequired();
        $form->addSubmit('save', 'Save About');

        $form->onSuccess[] = function (Form $form, $values) use ($about): void {
            $this->pageFacade->updateAboutSection($about->id, (array)$values);
            $this->flashMessage('About section updated successfully.', 'success');
            $this->redirect('this');
        };

        return $form;
    }

    /* ------------------- Contact Section Edit Form ------------------- */

    /**
     * Create a form to edit the Contact section.
     *
     * @return Form
     */
    public function createComponentContactForm(): Form
    {
        $form = new Form;
        $contact = $this->pageFacade->getContactInfo();

        $form->addText('name', 'Name:')
             ->setDefaultValue($contact->name)
             ->setRequired();
        $form->addText('address', 'Address:')
             ->setDefaultValue($contact->address)
             ->setRequired();
        $form->addText('ico', 'IČO:')
             ->setDefaultValue($contact->ico)
             ->setRequired();
        $form->addText('phone', 'Phone:')
             ->setDefaultValue($contact->phone)
             ->setRequired();
        $form->addText('email', 'Email:')
             ->setDefaultValue($contact->email)
             ->setRequired();
        $form->addTextArea('map_embed', 'Map Embed Code:')
             ->setDefaultValue($contact->map_embed)
             ->setRequired();
        $form->addSubmit('save', 'Save Contact');

        $form->onSuccess[] = function (Form $form, $values) use ($contact): void {
            $this->pageFacade->updateContactInfo($contact->id, (array)$values);
            $this->flashMessage('Contact information updated successfully.', 'success');
            $this->redirect('this');
        };

        return $form;
    }

    /* ------------------- Editing Advantage Items ------------------- */

    /**
     * Renders a form to edit a single Advantage.
     *
     * @param int $id
     */
    public function renderEditAdvantage(int $id): void
    {
        $advantage = $this->pageFacade->getAdvantages()->get($id);
        if (!$advantage) {
            $this->error('Advantage not found');
        }
        $this->template->advantage = $advantage;
    }

    /**
     * Create a form to edit an Advantage.
     *
     * @return Form
     */
    public function createComponentAdvantageForm(): Form
    {
        $form = new Form;
        $id = (int)$this->getParameter('id');
        $advantage = $this->pageFacade->getAdvantages()->get($id);
        if (!$advantage) {
            $this->error('Advantage not found');
        }
        $form->addText('icon', 'Icon:')
             ->setDefaultValue($advantage->icon)
             ->setRequired();
        $form->addText('title', 'Title:')
             ->setDefaultValue($advantage->title)
             ->setRequired();
        $form->addTextArea('description', 'Description:')
             ->setDefaultValue($advantage->description)
             ->setRequired();
        $form->addInteger('ordering', 'Ordering:')
             ->setDefaultValue($advantage->ordering)
             ->setRequired();
        $form->addSubmit('save', 'Save Advantage');

        $form->onSuccess[] = function (Form $form, $values) use ($advantage): void {
            $this->pageFacade->updateAdvantage($advantage->id, (array)$values);
            $this->flashMessage('Advantage updated successfully.', 'success');
            $this->redirect('Dashboard:default');
        };

        return $form;
    }
}
