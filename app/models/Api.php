<?php
class Api
{
    private $db;

    public function __construct()
    {
        $this->db = new Database;
        $this->brain = new Brain($this->db);
    }

    public function findAlldishesRaw()  //returns raw dish database 
    {
        $this->db->query('SELECT * FROM dishes');

        $results = $this->db->resultSet();

        return $results;
    }

    public function findAlldishes() //returns all dishes with their data in place
    {
        $results = $this->findAlldishesRaw();
        $output = [];
        foreach ($results as $dish) {
            array_push($output, $this->fillDishWithData($dish));
        }

        return $output;
    }

    public function findDishByIDRaw($id) //returns a raw dish from the database
    {
        $this->db->query('SELECT * FROM dishes WHERE id = :id');

        $this->db->bind(':id', $id);

        return  $this->db->single();
    }

    public function findRecipieByID($id) //returns a recipie by its ID
    {
        $this->db->query('SELECT * FROM recipies WHERE id = :id');

        $this->db->bind(':id', $id);

        $result = $this->db->single();
        if ($result == false) {
            return "No recipie";
        } else {
            return $result->steps;
        }
    }

    public function findDishByID($dish_id) //returns a dish with all data in place
    {
        $result = $this->findDishByIDRaw($dish_id);
        return  $this->fillDishWithData($result);
    }

    public function getUsedDishes($user_id)
    {
        $this->db->query('SELECT dish_id FROM used WHERE user_id = :user_id');
        $this->db->bind(":user_id", $user_id);
        $used_raw = $this->db->resultSet();
        $output = [];
        foreach ($used_raw as $used) {
            array_push($output, $used->dish_id);
        }

        return $output;
    }

    public function addDishToUsed($dish_id, $user_id)
    {
        //sprawdzam czy duplikat (nie wiem jak inaczej xddddd)
        $this->db->query('SELECT count(*) as n FROM used WHERE dish_id = :dish_id and user_id = :user_id LIMIT 1');
        $this->db->bind(":dish_id", $dish_id);
        $this->db->bind(":user_id", $user_id);
        if ($this->db->single()->n > 0) {
            die("Dish is already noted as used");
        }
        $this->db->query('INSERT INTO used (dish_id, user_id) VALUES (:dish_id, :user_id)');
        $this->db->bind(":dish_id", $dish_id);
        $this->db->bind(":user_id", $user_id);
        $this->db->execute();
    }

    public function removeDishFromUsed($dish_id, $user_id)  
    {
        $this->db->query("DELETE FROM `used` WHERE dish_id = :dish_id and user_id = :user_id");
        $this->db->bind("dish_id", $dish_id);
        $this->db->bind("user_id", $user_id);
        $this->db->execute();
    }

    public function getRecommendations($user_id, $amount)
    {
        $raw = $this->brain->getRecommendedRaw($user_id, $amount);
        $out = array();
        foreach($raw as $dish) {
            array_push($out, $this->fillDishWithData($dish));
        }
        return $out;
    }

    public function findTagByID($tag_id) //returns tag's name by its id
    {
        $this->db->query('SELECT name FROM tags WHERE id = :id');

        $this->db->bind(':id', $tag_id);

        return  $this->db->single();
    }

    public function getTags($tag_id_string) //converts tags_id into an array of tag names
    {
        $tags = explode(",", $tag_id_string);
        $tag_names = [];
        foreach ($tags as $tag_id) {
            $tag = $this->findTagByID($tag_id);
            if ($tag == false) {
                continue;
            }
            $tag_name = $tag->name;
            array_push($tag_names, $tag_name);
        }
        return $tag_names;
    }

    public function fillDishWithData($dish) //puts recipie and tags into the recipie and returns it
    {
        //Adding tags from tag ids
        $dish->tags = $this->getTags($dish->tags_id);
        unset($dish->tags_id);

        //Adding recipie from recipie_id
        $recipie = $this->findRecipieByID($dish->recipie_id);
        unset($dish->recipie_id);
        $dish->recipie = utf8_encode($recipie);

        return $dish;
    }

    public function getESPRecipie($user_id) //returns the dish the user wants to be displayed
    {
        $this->db->query('SELECT dish_id FROM esp_display WHERE user_id = :user_id');
        $this->db->bind(':user_id', $user_id);
        $dish_id = $this->db->single()->dish_id;
        $recipie = $this->findDishByID($dish_id);
        return $recipie;
    }

    public function setESPRecipie($user_id, $dish_id)
    {
        //check if the user already has a recipie set
        $this->db->query('SELECT * FROM esp_display WHERE user_id = :user_id');
        $this->db->bind(':user_id', $user_id);
        if(!$this->db->single())
        {
            $this->db->query('INSERT INTO esp_display (user_id, dish_id) VALUES (:user_id, :dish_id)');
        }
        else
        {
            $this->db->query('UPDATE esp_display SET dish_id=:dish_id WHERE user_id=:user_id');
        }
        $this->db->bind(':user_id', $user_id);
        $this->db->bind(':dish_id', $dish_id);
        $this->db->execute();
    }
}
