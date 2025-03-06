<?php

namespace App\Model;

use Nette\Database\Context;

class CourseFacade
{
    private $database;

    public function __construct(Context $database)
    {
        $this->database = $database;
    }

    /**
     * Retrieves all courses from the database.
     * @return array|\Nette\Database\Table\Selection
     */
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
}