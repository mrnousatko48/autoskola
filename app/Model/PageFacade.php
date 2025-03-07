<?php

namespace App\Model;

use Nette\Database\Context;

/**
 * PageFacade consolidates page content retrieval from multiple tables.
 */
class PageFacade {
    /** @var Context */
    private $database;

    /**
     * Constructor.
     *
     * @param Context $database Nette Database Context for database operations.
     */
    public function __construct(Context $database) {
        $this->database = $database;
    }

    // Common fetch method for sections where only one row is expected.
    private function fetchSingle(string $table) {
        return $this->database->table($table)->fetch();
    }

    // Common update method.
    private function updateRecord(string $table, int $id, array $values): void {
        $this->database->table($table)->get($id)->update($values);
    }

    public function getHeroSection() {
        return $this->fetchSingle('hero_section');
    }

    public function getAboutSection() {
        return $this->fetchSingle('about_section');
    }

    public function getContactInfo() {
        return $this->fetchSingle('contact_info');
    }

    public function getAdvantages() {
        return $this->database->table('advantages')->order('ordering ASC');
    }

    public function getOfferings() {
        return $this->database->table('offerings')->order('ordering ASC');
    }

    public function getAllCourses() {
        return $this->database->table('courses')->fetchAll();
    }

    /**
     * Retrieves a single course by ID.
     * @param int $id
     * @return \Nette\Database\Table\ActiveRow|null
     */
    public function getCourseById(int $id) {
        return $this->database->table('courses')->get($id);
    }

    public function addCourse(string $name, ?string $description, ?string $image, float $price): void {
        $this->database->table('courses')->insert([
            'name' => $name,
            'description' => $description,
            'image' => $image,
            'price' => $price,
        ]);
    }

    public function getGroupedCoursePrices(): array {
        $rows = $this->database->table('other_services')->order('section ASC, ordering ASC');
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row->section][] = $row;
        }
        return $grouped;
    }

    public function updateHeroSection(int $id, array $values): void {
        $this->updateRecord('hero_section', $id, $values);
    }

    public function updateAboutSection(int $id, array $values): void {
        $this->updateRecord('about_section', $id, $values);
    }

    public function updateContactInfo(int $id, array $values): void {
        $this->updateRecord('contact_info', $id, $values);
    }

    public function updateAdvantage(int $id, array $values): void {
        $this->updateRecord('advantages', $id, $values);
    }

    public function updatePrice(int $id, array $values): void {
        $this->updateRecord('other_services', $id, $values);
    }

    public function getPriceById(int $id) {
        return $this->database->table('other_services')->get($id);
    }

    public function updateCourse(int $id, array $values): void {
        $this->updateRecord('courses', $id, $values);
    }

public function getOtherServices(int $courseId) {
    return $this->database->table('other_services')
        ->where('course_id', $courseId)
        ->order('ordering ASC');
}

public  function getOfferById(int $id) {
    return $this->database->table('offerings')->get($id);
}

public function updateOffer(int $id, array $values): void {
    $this->updateRecord('offerings', $id, $values);
}
}