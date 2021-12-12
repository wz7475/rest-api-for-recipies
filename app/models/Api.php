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
        $this->db->query('SELECT * FROM dishes WHERE id = :id');

        $this->db->bind(':id', $id);

        $row = $this->db->single();

        //Adding tags from tag ids
        $tags = explode(",", $row->tags_id);
        $tag_names = [];
        foreach($tags as $tag_id)
        {
            array_push($tag_names, $this->findTagByID($tag_id)->name);
        }
        unset($row->tags_id);
        $row->tags = $tag_names;

        //Adding recipie from recipie_id
        $recipie = $this->getRecipieByID($row->recipie_id)->steps;
        //$recipie = str_replace("\n", "\\\\n", $recipie); //JSON ma jakieś problemy ze znakami nowej lini...
        unset($row->recipie_id);
        $row->recipie = utf8_encode($recipie);
    
        return $row;
    }

    public function findTagByID($tag_id)
    {
        $this->db->query('SELECT name FROM tags WHERE id = :id');

        $this->db->bind(':id', $tag_id);

        return  $this->db->single();
    }

    public function getRecipieByID($recipie_id)
    {
        $this->db->query('SELECT * FROM recipies WHERE id = :id');

        $this->db->bind(':id', $recipie_id);

        return  $this->db->single();
    }
}
