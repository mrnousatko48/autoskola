<?php

namespace App\Model;

use Nette\Database\Context;

class RegistrationFacade
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

    // Optional: Method to fetch registrations (e.g., for admin purposes)
    public function getRegistrations()
    {
        return $this->database->table('registrations')->fetchAll();
    }
}