<?php

namespace App\Model;

use Nette\Database\Context;

/**
 * PageFacade consolidates page content retrieval from multiple tables.
 * This facade provides methods to fetch the hero, about, advantages, offerings,
 * contact, and course price data from the database.
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

    /**
     * Get the hero section data.
     *
     * @return \Nette\Database\Table\ActiveRow|null Returns the hero section row.
     */
    public function getHeroSection() {
        return $this->database->table('hero_section')->fetch();
    }

    /**
     * Get the about section data.
     *
     * @return \Nette\Database\Table\ActiveRow|null Returns the about section row.
     */
    public function getAboutSection() {
        return $this->database->table('about_section')->fetch();
    }

    /**
     * Get all advantages for the page.
     *
     * @return \Nette\Database\Table\Selection Returns a collection of advantage rows ordered by 'ordering'.
     */
    public function getAdvantages() {
        return $this->database->table('advantages')->order('ordering ASC');
    }

    /**
     * Get all offerings.
     *
     * @return \Nette\Database\Table\Selection Returns a collection of offering rows ordered by 'ordering'.
     */
    public function getOfferings() {
        return $this->database->table('offerings')->order('ordering ASC');
    }

    /**
     * Get the contact section information.
     *
     * @return \Nette\Database\Table\ActiveRow|null Returns the contact information row.
     */
    public function getContactInfo() {
        return $this->database->table('contact_info')->fetch();
    }


    public function getAllCourses()
    {
        return $this->database->table('courses')->fetchAll();
    }

    /**
     * Retrieves a single course by ID.
     * @param int $id
     * @return \Nette\Database\Table\ActiveRow|null
     */
    public function getCourseById(int $id)
    {
        return $this->database->table('courses')->get($id);
    }

    public function addCourse(string $name, ?string $description, ?string $image, float $price): void
{
    $this->database->table('courses')->insert([
        'name' => $name,
        'description' => $description,
        'image' => $image,
        'price' => $price,
    ]);
}

public function getGroupedCoursePrices(): array
{
    $rows = $this->database->table('course_prices')->order('section ASC, ordering ASC');
    $grouped = [];
    foreach ($rows as $row) {
        $grouped[$row->section][] = $row;
    }
    return $grouped;
}

public function updateHeroSection(int $id, array $values): void
{
    $this->database->table('hero_section')->get($id)->update($values);
}

public function updateAboutSection(int $id, array $values): void
{
    $this->database->table('about_section')->get($id)->update($values);
}

public function updateContactInfo(int $id, array $values): void
{
    $this->database->table('contact_info')->get($id)->update($values);
}

public function updateAdvantage(int $id, array $values): void
{
    $this->database->table('advantages')->get($id)->update($values);
}

public function updatePrice(int $id, array $values): void
{
    $this->database->table('course_prices')->get($id)->update($values);
}

public function getPriceById(int $id)
{
    return $this->database->table('course_prices')->get($id);
}

public function updateCourse(int $id, array $values): void
{
    $this->database->table('courses')->get($id)->update($values);

}
}
