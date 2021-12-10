<?php
class Api {
    private $db;

    public function __construct() {
        $this->db = new Database;
    }

    public function findAlldishes() {
        $this->db->query('SELECT * FROM dishes');

        $results = $this->db->resultSet();

        return $results;
    }

    public function findDishById($id) {
        $this->db->query('SELECT name FROM dishes WHERE id = :id');

        $this->db->bind(':id', $id);

        $row = $this->db->single();

        return $row;
    }

   
}
