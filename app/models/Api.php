<?php
class Api
{
    private $db;

    public function __construct()
    {
        $this->db = new Database;
    }

    public function findAlldishesRaw()
    {
        $this->db->query('SELECT * FROM dishes');

        $results = $this->db->resultSet();

        return $results;
    }

    public function findAlldishes()
    {
        $results = $this->findAlldishesRaw();
        $output = [];
        foreach($results as $dish)
        {
            array_push($output, $this->fillDishWithData($dish));
        }

        return $output;
    }

    public function findDishById($id)
    {
        $this->db->query('SELECT * FROM dishes WHERE id = :id');

        $this->db->bind(':id', $id);

        $row = $this->db->single();

        return $row;
    }

    public function getRecipieByIDRaw($recipie_id)
    {
        $this->db->query('SELECT * FROM recipies WHERE id = :id');

        $this->db->bind(':id', $recipie_id);

        return  $this->db->single();
    }

    public function getRecipieByID($recipie_id)
    {
        $result = $this->getRecipieByIDRaw($recipie_id);

        return  $this->fillDishWithData($result);
    }

    public function addDishToUsed($dish_id, $user_id)
    {
        $this->db->query('INSERT INTO used (dish_id, user_id) VALUES (:dish_id, :user_id)');
        return $this->db->execute();
    }

    public function getRecommendations($user_id, $amount)
    {
        //Get all disliked tags
        $this->db->query('
        SELECT tags_id FROM dishes
        WHERE id in (
            SELECT dish_id FROM dislikes
            WHERE user_id = :user_id
            )
        ');
        $this->db->bind(':user_id', $user_id);
        $disliked_tags = $this->db->resultSet();

        //count them
        $bucket = [];
        foreach ($disliked_tags as $tags) {
            $tags = explode(",", $tags->tags_id);
            foreach ($tags as $tag) {
                if (isset($bucket[$tag])) {
                    $bucket[$tag]++;
                } else {
                    $bucket[$tag] = 1;
                }
            }
        }

        //rank them
        $worst_tag_occurences = max($bucket);
        var_dump($worst_tag_occurences);

        //get all recipies that are not disliked
        $this->db->query('
        SELECT * FROM dishes
        WHERE id not in (
            SELECT dish_id FROM dislikes 
            WHERE user_id = :user_id
            )
        ');
        $this->db->bind(':user_id', $user_id);
        $available_dishes = $this->db->resultSet();
    }

    public function findTagByID($tag_id)
    {
        $this->db->query('SELECT name FROM tags WHERE id = :id');

        $this->db->bind(':id', $tag_id);

        return  $this->db->single();
    }

    
    public function getTags($tag_id_string)
    {
        $tags = explode(",", $tag_id_string);
        $tag_names = [];
        foreach ($tags as $tag_id) {
            array_push($tag_names, $this->findTagByID($tag_id)->name);
        }
        return $tag_names;
    }

    public function fillDishWithData($dish)
    {
         //Adding tags from tag ids
         $dish->tags = $this->getTags($dish->tags_id);
         unset($dish->tags_id);
 
         //Adding recipie from recipie_id
         $recipie = $this->getRecipieByID($dish->recipie_id)->steps;
         unset($dish->recipie_id);
         $dish->recipie = utf8_encode($recipie);

         return $dish;
    }
}
