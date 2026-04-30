<?php

class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function countByRole($role) {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) as count FROM users WHERE role = :role');
        $stmt->execute([':role' => $role]);
        $result = $stmt->fetch();
        return (int) $result['count'];
    }

    public function getAllByRole($role) {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE role = :role ORDER BY name');
        $stmt->execute([':role' => $role]);
        return $stmt->fetchAll();
    }

    public function findById($id) {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
}