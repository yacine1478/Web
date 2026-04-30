<?php

class Semester {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        $stmt = $this->pdo->query('SELECT * FROM semesters ORDER BY academic_year DESC, label');
        return $stmt->fetchAll();
    }

    public function create($label, $academicYear) {
        $stmt = $this->pdo->prepare('INSERT INTO semesters (label, academic_year) VALUES (:label, :academic_year)');
        $stmt->execute([
            ':label' => $label,
            ':academic_year' => $academicYear
        ]);
        return $this->pdo->lastInsertId();
    }

    public function setActive($id) {
        $this->pdo->prepare('UPDATE semesters SET is_active = FALSE')->execute();
        $stmt = $this->pdo->prepare('UPDATE semesters SET is_active = TRUE WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function delete($id) {
        // Check if there are related courses
        $stmt = $this->pdo->prepare('SELECT COUNT(*) as count FROM courses WHERE semester_id = :id');
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        if ($result['count'] > 0) {
            return false;
        }

        $stmt = $this->pdo->prepare('DELETE FROM semesters WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return true;
    }
}