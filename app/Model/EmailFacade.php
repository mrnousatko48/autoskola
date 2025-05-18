<?php

namespace App\Model;

use Nette\Database\Context;

class EmailFacade
{
    private $database;

    public function __construct(Context $database)
    {
        $this->database = $database;
    }

    public function createRegistration(array $data)
    {
        try {
            return $this->database->table('registrations')->insert($data);
        } catch (\Exception $e) {
            throw new \Exception("Error creating registration: " . $e->getMessage());
        }
    }

    public function getTemplateByName(string $name): array
    {
        $template = $this->database->table('email_templates')->where('name', $name)->fetch();
        if (!$template) {
            throw new \Exception("Šablona $name nebyla nalezena.");
        }
        $data = $template->toArray();
        $data['pdf_paths'] = json_decode($data['pdf_paths'] ?? '[]', true); // pole cest k PDF
        return $data;
    }

    public function updateTemplate(string $name, array $data): void
    {
        $this->database->table('email_templates')->where('name', $name)->update($data);
    }

    public function getAllRegistrations(): array
    {
        return $this->database->table('registrations')->fetchAll();
    }

    public function acceptRegistration(int $registrationId): void
    {
        $this->database->table('registrations')
            ->where('id', $registrationId)
            ->update(['accepted' => true]);
    }

    public function deleteRegistration(int $registrationId): void
    {
        $this->database->table('registrations')
            ->where('id', $registrationId)
            ->delete();
    }
}