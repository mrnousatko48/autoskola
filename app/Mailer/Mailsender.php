<?php

namespace App\MailSender;

use Nette\Mail\Message;
use Nette\Mail\Mailer;
use Nette\Mail\SmtpMailer;
use Latte\Engine;

class MailSender
{
    public function __construct(
        private Mailer $mailer
    ) {}

    public function createNotificationEmail(string $name): Message
    {
        $latte = new Engine();
        $params = [
            'name' => $name,
            'content' => 'Default notification content.', // Will be overridden by registration data
        ];
        $html = $latte->renderToString(__DIR__ . '/registrationNotification.latte', $params);

        $mail = new Message;
        $mail->setFrom('burdadko.cczz@seznam.cz')
             ->addTo('burdadko.cczz@seznam.cz')
             ->setSubject('Notification Email')
             ->setHtmlBody($html);

        return $mail;
    }

    public function createRegistrationEmail(string $email, string $name, string $courseName, string $details): Message
    {
        $latte = new Engine();
        $params = [
            'email' => $email,
            'name' => $name,
            'content' => $details, // Use details as content for registration
            'courseName' => $courseName,
        ];
        $html = $latte->renderToString(__DIR__ . '/email.latte', $params);

        $mail = new Message;
        $mail->setFrom('burdadko.cczz@seznam.cz')
             ->addTo($email)
             ->setSubject('Nová registrace na kurz: ' . $courseName)
             ->setHtmlBody($html);

        return $mail;
    }

    public function sendRegistrationEmail(string $email, string $name, string $courseName, string $details): void
    {
        $mail = $this->createRegistrationEmail($email, $name, $courseName, $details);
        $this->mailer->send($mail);
    }
}