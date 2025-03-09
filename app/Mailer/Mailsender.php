<?php
namespace App\MailSender;

use Nette\Mail\Message;
use Nette\Mail\Mailer;
use Latte\Engine;

class MailSender
{
    public function __construct(
        private Mailer $mailer
    ) {}

    public function createRegistrationEmail(
        string $adminEmail,
        string $userEmail,
        string $name,
        string $courseName,
        string $address,
        string $phone,
        string $registrationDate
    ): Message {
        $latte = new Engine();
        $params = [
            'userEmail' => $userEmail,
            'name' => $name,
            'courseName' => $courseName,
            'address' => $address,
            'phone' => $phone,
            'registrationDate' => $registrationDate,
        ];
        $html = $latte->renderToString(__DIR__ . '/email.latte', $params);

        $mail = new Message;
        $mail->setFrom('burdadko.cczz@seznam.cz')
             ->addTo($adminEmail)
             ->setSubject('Nová registrace na kurz: ' . $courseName)
             ->setHtmlBody($html);

        return $mail;
    }

    public function sendRegistrationEmail(
        string $adminEmail,
        string $userEmail,
        string $name,
        string $courseName,
        string $address,
        string $phone,
        string $registrationDate
    ): void {
        $mail = $this->createRegistrationEmail(
            $adminEmail,
            $userEmail,
            $name,
            $courseName,
            $address,
            $phone,
            $registrationDate
        );
        $this->mailer->send($mail);
    }
}