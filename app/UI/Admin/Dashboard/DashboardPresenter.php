<?php
declare(strict_types=1);

namespace App\UI\Admin\Dashboard;

use Nette\Application\UI\Presenter;
use Nette\Application\UI\Form;
use App\Model\PageFacade;
use App\Model\UserFacade;
use App\Model\emailFacade;
use App\Utils\PdfUploader;

/**
 * DashboardPresenter provides separate admin pages for editing each section.
 */
final class DashboardPresenter extends Presenter
{
    /** @var PageFacade */
    private PageFacade $pageFacade;
    private UserFacade $userFacade;
    private emailFacade $emailFacade;

    /**
     * Constructor.
     *
     * @param PageFacade $pageFacade Central facade for retrieving and updating page content.
     */
    public function __construct(PageFacade $pageFacade, UserFacade $userFacade, emailFacade $emailFacade)
    {
        parent::__construct();
        $this->pageFacade = $pageFacade;
        $this->userFacade = $userFacade;
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

    /**
     * Default dashboard view with navigation buttons.
     */
    public function renderDefault(): void
    {
    }
    public function renderUser(): void
    {
        $this->template->userData = $this->userFacade->getAllUsers();
    }

    /**
     * Render the Hero Section edit page.
     */
    public function renderHero(): void
    {
        $this->template->hero = $this->pageFacade->getHeroSection();
        $this->template->setFile(__DIR__ . '/Templates/hero.latte');
    }

    public function renderEmail(): void
    {
        $this->template->userTemplate = $this->emailFacade->getTemplateByName('usr_confirmation');
        $this->template->adminTemplate = $this->emailFacade->getTemplateByName('admin_notification');
        $this->template->setFile(__DIR__ . '/Templates/email.latte');
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

    public function renderAdvantages(): void
    {
        $id = $this->getParameter('id');
        if ($id) {
            $advantage = $this->pageFacade->getAdvantages()->get((int)$id);
       
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
        $id = $this->getParameter('id');
        if ($id) {
            $this->template->offering = $this->pageFacade->getOfferById((int)$id);
        }
        $this->template->offerings = $this->pageFacade->getOfferings();
        $this->template->setFile(__DIR__ . '/Templates/offerings.latte');
    }

    /**
     * Render the Course Prices ("Ceník") edit page.
     */
    public function renderPrices(): void
    {
        $id = $this->getParameter('id');
        if ($id) {
            $this->template->price = $this->pageFacade->getPriceById((int)$id);
        }
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

    /**
     * Render the Add Course page.
     */
public function renderAddCourse(): void
{
    $this->template->courses = $this->pageFacade->getAllCourses(); // For consistency with courses.latte
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
     *                                      'heading' => ['type' => 'text', 'label' => 'Nadpis:', 'required' => true],
     *                                      'subheading'  => ['type' => 'textArea', 'label' => 'Podnadpis:', 'required' => true],
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
            
            // Set the default value from config or the entity
            if (array_key_exists('default', $config)) {
                $field->setDefaultValue($config['default']);
            } else {
                $field->setDefaultValue($entity->{$name});
            }
            
            // If an HTML type is provided (e.g. 'date'), set it
            if (isset($config['htmlType'])) {
                $field->setHtmlType($config['htmlType']);
            }
            
            if (!empty($config['required'])) {
                $field->setRequired();
            }
            
            // Add Bootstrap styling for most fields (skip hidden fields)
            if ($config['type'] !== 'hidden') {
                $field->getControlPrototype()->addClass('form-control');
            }
        }
        
        // Add a submit button with Bootstrap classes
        $form->addSubmit('save', 'Uložit')
             ->getControlPrototype()->addClass('btn btn-primary');
        
        $form->onSuccess[] = function (Form $form, $values) use ($entity, $updateCallback, $flashMessage, $redirectDestination): void {
            $updateCallback($entity->id, (array)$values);
            $this->flashMessage($flashMessage, 'success');
            $this->redirect($redirectDestination);
        };
    
        return $form;
    }

    /**
     * Create a form to add a new Course.
     *
     * @return Form
     */
public function createComponentAddCourseForm(): Form
{
    $form = new Form;
    $fields = [
        'name' => ['type' => 'text', 'label' => 'Název kurzu:', 'required' => true],
        'description' => ['type' => 'textArea', 'label' => 'Popis:', 'required' => true],
        'content' => ['type' => 'textArea', 'label' => 'Podrobný obsah:', 'required' => false], // New field
        'price' => ['type' => 'text', 'label' => 'Cena:', 'required' => true],
        'location' => ['type' => 'text', 'label' => 'Adresa:', 'required' => true],
        'start_date' => ['type' => 'text', 'label' => 'Datum:', 'required' => true, 'htmlType' => 'date'],
        'start_time' => ['type' => 'text', 'label' => 'Od:', 'required' => true, 'htmlType' => 'time'],
    ];

    $form->addUpload('image', 'Obrázek:')
         ->setHtmlAttribute('class', 'form-control');

    foreach ($fields as $name => $config) {
        $method = 'add' . ucfirst($config['type']);
        $field = $form->$method($name, $config['label'] ?? '');
        if (isset($config['htmlType'])) {
            $field->setHtmlType($config['htmlType']);
        }
        if (!empty($config['required'])) {
            $field->setRequired();
        }
        if ($config['type'] !== 'hidden') {
            $field->getControlPrototype()->addClass('form-control');
        }
    }

    $form->addCheckbox('show_ribbon', 'Zobrazit ribbon')
         ->setDefaultValue(true);

    $form->addSubmit('save', 'Přidat kurz')
        ->getControlPrototype()->addClass('btn btn-primary')
        ->setAttribute('type', 'submit');


    $form->onSuccess[] = function (Form $form, $values) {
    $data = (array)$values;

    try {
        $image = $data['image'];
        $imagePath = \App\Utils\ImageUploader::uploadImage($image, 'uploads/courses', null);
        if (!$imagePath && $image->isOk()) {
            throw new \Exception('Nepodařilo se nahrát obrázek.');
        }

        $this->pageFacade->addCourse(
            $data['name'],
            $data['description'],
            $data['content'],
            $imagePath,
            floatval(str_replace(',', '.', $data['price'])),
            $data['location'],
            $data['start_date'],
            $data['start_time'],
            (bool)$data['show_ribbon']
        );

        $this->flashMessage('Kurz byl úspěšně přidán.', 'success');

    } catch (\Exception $e) {
        $this->flashMessage('Nastala chyba při přidávání kurzu: ' . $e->getMessage(), 'danger');
        $this->redirect('this');
    }

    $this->redirect('Dashboard:courses');
};


    $form->getElementPrototype()->enctype = 'multipart/form-data';

    return $form;
}

    /**
     * Create a form to edit an Offering.
     *
     * @return Form
     */
    public function createComponentOfferForm(): Form
    {
        $id = (int)$this->getParameter('id');
        if (!$id) {
            $this->error('Nabídka nenalezena');
        }
        $offer = $this->pageFacade->getOfferById($id);
        if (!$offer) {
            $this->error('Nabídka nenalezena');
        }
        $fields = [
            'label'   => ['type' => 'text',     'label' => 'Označení:', 'required' => true],
            'content' => ['type' => 'textArea', 'label' => 'Obsah:',    'required' => true],
        ];

        $form = $this->createEditForm(
            $offer,
            $fields,
            function ($submittedId, $values) {
                $offerId = (int)$values['id'];
                $this->pageFacade->updateOffer($offerId, (array)$values);
            },
            'Nabídka byla úspěšně aktualizována.',
            'Dashboard:offerings'
        );

        // Add a hidden field for the offer ID
        $form->addHidden('id')->setDefaultValue($offer->id);

        // Set the form action so that the ID remains in the URL upon submission
        $form->setAction($this->link('Dashboard:offerings', ['id' => $offer->id]));

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
            'heading'     => ['type' => 'text',     'label' => 'Nadpis:',       'required' => true],
            'subheading'  => ['type' => 'textArea', 'label' => 'Podnadpis:',    'required' => true],
            'button_text' => ['type' => 'text',     'label' => 'Text tlačítka:', 'required' => true],
            'button_link' => ['type' => 'text',     'label' => 'Odkaz tlačítka:', 'required' => true],
        ];

        return $this->createEditForm(
            $hero,
            $fields,
            fn($id, $values) => $this->pageFacade->updateHeroSection($id, $values),
            'Hlavní sekce byla úspěšně aktualizována.',
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
            'heading'  => ['type' => 'text',     'label' => 'Nadpis:', 'required' => true],
            'alt_text' => ['type' => 'text',     'label' => 'Alternativní text:', 'required' => true],
            'content'  => ['type' => 'textArea', 'label' => 'Obsah:',  'required' => true],
        ];

        $form = $this->createEditForm(
            $about,
            $fields,
            function ($id, $values) use ($about) {
                /** @var \Nette\Http\FileUpload $image */
                $image = $values['image'];
                $values['image'] = \App\Utils\ImageUploader::uploadImage($image, 'uploads/about', $about->image);
                $this->pageFacade->updateAboutSection($id, $values);
            },
            'Sekce O nás byla úspěšně aktualizována.',
            'Dashboard:about'
        );

        $form->addHidden('id')->setDefaultValue($about->id);

        if ($form->getComponent('image', false)) {
            $form->removeComponent($form['image']);
        }
        $form->addUpload('image', 'Obrázek:')
             ->setHtmlAttribute('class', 'form-control');

        $form->getElementPrototype()->enctype = 'multipart/form-data';

        $form->setAction($this->link('Dashboard:about', ['id' => $about->id]));

        return $form;
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
            'name'      => ['type' => 'text',     'label' => 'Jméno:',      'required' => true],
            'address'   => ['type' => 'text',     'label' => 'Adresa:',     'required' => true],
            'ico'       => ['type' => 'text',     'label' => 'IČO:',        'required' => true],
            'phone'     => ['type' => 'text',     'label' => 'Telefon:',    'required' => true],
            'email'     => ['type' => 'text',     'label' => 'Email:',      'required' => true],
            'map_embed' => ['type' => 'textArea', 'label' => 'Kód vložení mapy:', 'required' => true],
        ];

        return $this->createEditForm(
            $contact,
            $fields,
            fn($id, $values) => $this->pageFacade->updateContactInfo($id, $values),
            'Kontaktní informace byly úspěšně aktualizovány.',
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
            $this->error('Výhoda nenalezena');
        }
        $fields = [
            'title'       => ['type' => 'text',     'label' => 'Název:',      'required' => true],
            'description' => ['type' => 'textArea', 'label' => 'Popis:',       'required' => true],
        ];
    
        $form = $this->createEditForm(
            $advantage,
            $fields,
            fn($id, $values) => $this->pageFacade->updateAdvantage($id, $values),
            'Výhoda byla úspěšně aktualizována.',
            'Dashboard:advantages'
        );
    
        $form->addHidden('id')->setDefaultValue($advantage->id);
    
        $form->setAction($this->link('Dashboard:advantages', ['id' => $advantage->id]));
    
        return $form;
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
            $this->error('Cena nenalezena');
        }
        $fields = [
            'item'        => ['type' => 'text',     'label' => 'Položka:',    'required' => true],
            'price'       => ['type' => 'text',     'label' => 'Cena:',       'required' => true],
            'description' => ['type' => 'textArea', 'label' => 'Popis:'],
            'section'     => ['type' => 'hidden',   'label' => ''],
        ];
    
        $form = $this->createEditForm(
            $price,
            $fields,
            fn($id, $values) => $this->pageFacade->updatePrice($id, $values),
            'Cena byla úspěšně aktualizována.',
            'Dashboard:prices'
        );
        
        $form->addHidden('id')->setDefaultValue($price->id);
        
        $form->setAction($this->link('Dashboard:prices', ['id' => $price->id]));
        
        return $form;
    }

    /**
     * Create a form to edit a Course.
     *
     * @return Form
     */
    public function createComponentCourseForm(): Form
    {
        $id = $this->getParameter('id');
        if (!$id) {
            $this->error('Kurz nenalezen');
        }
        $course = $this->pageFacade->getCourseById((int)$id);
        if (!$course) {
            $this->error('Kurz nenalezen');
        }
        
        $fields = [
            'name'        => ['type' => 'text',     'label' => 'Název kurzu:', 'required' => true],
            'description' => ['type' => 'textArea', 'label' => 'Popis:',        'required' => true],
            'content'     => ['type' => 'textArea', 'label' => 'Podrobný obsah:', 'required' => false],
            'price'       => ['type' => 'text',     'label' => 'Cena:',         'required' => true],
            'location'    => ['type' => 'text',     'label' => 'Adresa:',       'required' => true],
            'start_date'  => ['type' => 'text',     'label' => 'Začíná:',       'required' => true, 'htmlType' => 'date'],
        ];
    
        $form = $this->createEditForm(
            $course,
            $fields,
            function ($submittedId, $values) use ($course) {
                /** @var \Nette\Http\FileUpload $image */
                $image = $values['image'];
                $values['image'] = \App\Utils\ImageUploader::uploadImage($image, 'uploads/courses', $course->image);
                $values['show_ribbon'] = $values['show_ribbon'];
                $courseId = (int)$values['id'];
                $this->pageFacade->updateCourse($courseId, (array)$values);
            },
            'Kurz byl úspěšně aktualizován.',
            'Dashboard:courses'
        );
    
        $form->addHidden('id')->setDefaultValue($course->id);
    
        if ($course->start_date instanceof \DateTimeInterface) {
            $form->getComponent('start_date');
        }
    
        if ($form->getComponent('image', false)) {
            $form->removeComponent($form['image']);
        }
        $form->addUpload('image', 'Obrázek:')
             ->setHtmlAttribute('class', 'form-control');
    
        $form->addCheckbox('show_ribbon', 'Zobrazit ribbon')
             ->setDefaultValue($course->show_ribbon ?? true);
    
        $form->getElementPrototype()->enctype = 'multipart/form-data';
    
        if ($form->getComponent('save', false)) {
            $form->removeComponent($form['save']);
        }
        $form->addSubmit('save', 'Uložit')
            ->getControlPrototype()->addClass('btn btn-primary')
            ->setAttribute('type', 'submit');
    
        $form->setAction($this->link('Dashboard:courses', ['id' => $course->id]));
    
        return $form;
    }

    public function actionDeleteUser(int $id): void
    {
        if ($this->user->getIdentity()->id === $id) {
            $this->flashMessage('Nemůžeš odstranit sebe.', 'danger');
            $this->redirect('user');
        }
        $this->userFacade->deleteUser($id);
        $this->flashMessage('Uživatel byl úspěšně odstraněn.', 'success');
        $this->redirect('user');
    } 

    public function actionDeleteCourse(int $id): void
    {
        $this->pageFacade->deleteCourse($id);
        $this->flashMessage('Kurz byl úspěšně odstraněn.', 'success');
        $this->redirect('courses');
    }

    protected function createComponentUserEmailForm(): Form
    {
        $form = new Form;
        $defaults = $this->emailFacade->getTemplateByName('usr_confirmation');
    
        $form->addText('subject', 'Předmět:')
            ->setDefaultValue($defaults['subject'] ?? '')
            ->setRequired('Předmět je povinný.');
        $form->addTextArea('body', 'Tělo emailu (HTML):')
            ->setDefaultValue($defaults['body'] ?? '')
            ->setRequired('Tělo emailu je povinné.');
        $form->addText('admin_phone', 'Kontaktní telefon:')
            ->setDefaultValue($defaults['admin_phone'] ?? '');
        $form->addUpload('pdf_files', 'PDF přílohy:')
            ->setHtmlAttribute('multiple', 'multiple')
            ->addCondition(Form::FILLED)
                ->addRule(Form::MIME_TYPE, 'Nahraný soubor musí být PDF.', 'application/pdf');
    
        $form->addSubmit('submit', 'Uložit změny');
    
        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            $pdfPaths = [];
            if (!empty($values->pdf_files)) {
                $files = is_array($values->pdf_files) ? $values->pdf_files : [$values->pdf_files];
                $uploadDir = realpath(__DIR__ . '/../../../web/uploads/pdf');
                
                // Kontrola, zda adresář existuje, pokud ne, vytvořit ho
                if ($uploadDir === false) {
                    $uploadDir = __DIR__ . '/../../../web/uploads/pdf';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true); // Vytvoří adresář rekurzivně
                    }
                    $uploadDir = realpath($uploadDir);
                    if ($uploadDir === false) {
                        throw new \Exception('Nepodařilo se vytvořit nebo najít adresář pro upload: ' . $uploadDir);
                    }
                }
    
                $pdfPaths = PdfUploader::uploadMultiplePdfs($files, $uploadDir);
            } else {
                $current = $this->emailFacade->getTemplateByName('usr_confirmation');
                $pdfPaths = $current['pdf_paths'];
            }
    
            $this->emailFacade->updateTemplate('usr_confirmation', [
                'subject' => $values->subject,
                'body' => $values->body,
                'admin_phone' => $values->admin_phone,
                'pdf_paths' => json_encode($pdfPaths),
            ]);
            $this->flashMessage('Šablona byla uložena.', 'success');
            $this->redirect('this');
        };
    
        return $form;
    }

    public function userEmailFormSucceeded(Form $form, $values): void
    {
        $this->emailFacade->updateTemplate('usr_confirmation', [
            'subject' => $values->subject,
            'body' => $values->body,
            'admin_phone' => $values->admin_phone,
        ]);
        $this->flashMessage('Šablona pro uživatele byla aktualizována.', 'success');
        $this->redirect('this');
    }

    public function createComponentAdminEmailForm(): Form
    {
        $form = new Form;
        $defaults = $this->emailFacade->getTemplateByName('admin_notification');

        $form->addText('subject', 'Předmět:')
            ->setDefaultValue($defaults['subject'] ?? '')
            ->setRequired('Předmět je povinný.');
        $form->addTextArea('body', 'Tělo emailu (HTML):')
            ->setDefaultValue($defaults['body'] ?? '')
            ->setRequired('Tělo emailu je povinné.');
        $form->addEmail('recipient_email', 'Email administrátora:')
            ->setDefaultValue($defaults['recipient_email'] ?? '')
            ->setRequired('Email administrátora je povinný.');
        $form->addText('admin_phone', 'Kontaktní telefon:')
            ->setDefaultValue($defaults['admin_phone'] ?? '');
        $form->addSubmit('submit', 'Uložit změny');

        $form->onSuccess[] = [$this, 'adminEmailFormSucceeded'];
        return $form;
    }

    public function adminEmailFormSucceeded(Form $form, $values): void
    {
        $this->emailFacade->updateTemplate('admin_notification', [
            'subject' => $values->subject,
            'body' => $values->body,
            'recipient_email' => $values->recipient_email,
            'admin_phone' => $values->admin_phone,
        ]);
        $this->flashMessage('Šablona pro administrátora byla aktualizována.', 'success');
        $this->redirect('this');
    }
}