<?php
namespace App\MailSender;

use Nette\Mail\Message;
use Nette\Mail\Mailer;
use Latte\Engine;
use App\Model\EmailFacade;

class MailSender
{
    private Mailer $mailer;
    private EmailFacade $EmailFacade;

    public function __construct(
        Mailer $mailer,
        EmailFacade $EmailFacade
    ) {
        $this->mailer = $mailer;
        $this->EmailFacade = $EmailFacade;
    }

    private function createAdminRegistrationEmail(
        string $userEmail,
        string $name,
        string $courseName,
        string $userAddress, // Adresa uživatele
        string $userPhone,
        string $registrationDate
    ): Message {
        $latte = new Engine();
        $params = [
            'userEmail' => $userEmail,
            'name' => $name,
            'courseName' => $courseName,
            'userAddress' => $userAddress, // Adresa uživatele pro admina
            'userPhone' => $userPhone, 
            'registrationDate' => $registrationDate, 
        ]; 
 
        $template = $this->EmailFacade->getTemplateByName('admin_notification'); 
        if (!$template) { 
            throw new \Exception('Šablona admin_notification nebyla nalezena v databázi.'); 
        } 
 
        $adminEmail = $template['recipient_email']; 
        if (!$adminEmail) { 
            throw new \Exception('Email příjemce pro admin_notification není nastaven v datab ázi.');
        } 
 
        $latte->setLoader(new \Latte\Loaders\StringLoader()); 
        $subject = $latte->renderToString($template['subject'], $params); 
        $html = $latte->renderToString($template['body'], $params); 
 
        $mail = new Message;
        $mail->setFrom('burdadko.cczz@seznam.cz')
             ->addTo($adminEmail)
             ->setSubject($subject)
             ->setHtmlBody($html);

        return $mail;
    }

    private function createUserConfirmationEmail(
        string $userEmail,
        string $name,
        string $courseName,
        string $courseLocation, // Místo konání kurzu
        string $courseStartDate
    ): Message {
        $latte = new Engine();
        $template = $this->EmailFacade->getTemplateByName('usr_confirmation');
        if (!$template) {
            throw new \Exception('Šablona usr_confirmation nebyla nalezena v databázi.');
        }
    
        $params = [
            'name' => $name,
            'courseName' => $courseName,
            'courseLocation' => $courseLocation, // Místo konání kurzu pro uživatele
            'adminPhone' => $template['admin_phone'] ?? 'Není zadán telefon',
            'courseStartDate' => $courseStartDate,
        ];
    
        $latte->setLoader(new \Latte\Loaders\StringLoader());
        $subject = $latte->renderToString($template['subject'], $params);
        $html = $latte->renderToString($template['body'], $params);
    
        $mail = new Message;
        $mail->setFrom('burdadko.cczz@seznam.cz')
             ->addTo($userEmail)
             ->setSubject($subject)
             ->setHtmlBody($html);
    
        $pdfPath = __DIR__ . '/../../www/uploads/pdf/podminky.pdf';
        if (file_exists($pdfPath)) {
            $mail->addAttachment('podminky.pdf', file_get_contents($pdfPath), mime_content_type($pdfPath));
        }
    
        $pdfPath = __DIR__ . '/../../www/uploads/pdf/doktor.pdf';
        if (file_exists($pdfPath)) {
            $mail->addAttachment('doktor.pdf', file_get_contents($pdfPath), mime_content_type($pdfPath));
        }
    
        return $mail;
    }

    public function sendRegistrationEmail(
        string $userEmail,
        string $name,
        string $courseName,
        string $userAddress,    // Adresa uživatele
        string $courseLocation, // Místo konání kurzu
        string $userPhone,
        string $registrationDate,
        string $courseStartDate
    ): void {
        $adminMail = $this->createAdminRegistrationEmail(
            $userEmail,
            $name,
            $courseName,
            $userAddress,    // Adresa uživatele pro admina
            $userPhone,
            $registrationDate
        );
        
        $userMail = $this->createUserConfirmationEmail(
            $userEmail,
            $name,
            $courseName,
            $courseLocation, // Místo konání kurzu pro uživatele
            $courseStartDate
        );

        $this->mailer->send($adminMail);
        $this->mailer->send($userMail);
    }
}