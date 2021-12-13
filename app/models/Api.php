<?php
class Api
{
    private $db;

    public function __construct()
    {
        $this->db = new Database;
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

    public function findDishByID($recipie_id) //returns a dish with all data in place
    {
        $result = $this->findDishByIDRaw($recipie_id);
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

    public function getRankedTags($user_id)
    {
        /*
        IF a tag is in disliked dishes -> negative score
        IF a tag is in used dishes -> positive score
        */

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

        //Get all used tags (ergo liked tags)
        $this->db->query('
        SELECT tags_id FROM dishes
        WHERE id in (
            SELECT dish_id FROM used
            WHERE user_id = :user_id
            )
        ');
        $this->db->bind(':user_id', $user_id);
        $used_tags = $this->db->resultSet();

        //count them
        $bucket_disliked = [];
        foreach ($disliked_tags as $tags) {
            $tags = explode(",", $tags->tags_id);
            foreach ($tags as $tag) {
                if (isset($bucket_disliked[$tag])) {
                    $bucket_disliked[$tag]++;
                } else {
                    $bucket_disliked[$tag] = 1;
                }
            }
        }
        $bucket_used = [];
        foreach ($used_tags as $tags) {
            $tags = explode(",", $tags->tags_id);
            foreach ($tags as $tag) {
                if (isset($bucket_used[$tag])) {
                    $bucket_used[$tag]++;
                } else {
                    $bucket_used[$tag] = 1;
                }
            }
        }

        //normalize
        if (count($bucket_disliked)) $worst_tag_occurences = max($bucket_disliked);
        if (count($bucket_used)) $best_tag_occurences = max($bucket_used);
        $ranked_tags = [];

        foreach (array_keys($bucket_disliked) as $tag) {
            $ranked_tags[$tag] = - ($bucket_disliked[$tag] / $worst_tag_occurences);
        }

        foreach (array_keys($bucket_used) as $tag) {
            if (isset($ranked_tags[$tag])) {
            } else {
                $ranked_tags[$tag] = ($bucket_used[$tag] / $best_tag_occurences);
            }
        }
        return $ranked_tags;
    }

    public function getUsageRank($user_id)
    {
        $this->db->query('
        SELECT dish_id FROM used 
        WHERE user_id = :user_id
        ');
        $this->db->bind(':user_id', $user_id);
        $used_dishes = $this->db->resultSet();

        $rank_by_usage = [];
        foreach ($used_dishes as $dish) {
            //PÓŹNIEJ BĘDZIE TO FUNKCJA OD CZASU
            $rank_by_usage[$dish->dish_id] = -1;
        }
        return $rank_by_usage;
    }

    public function getRecommendations($amount, $user_id) //returns $amount of full dishes recommended to $user_id 
    {
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

        $rank_by_usage = $this->getUsageRank($user_id);
        $ranked_tags = $this->getRankedTags($user_id);

        //calculate each dishes score
        foreach ($available_dishes as $dish) {
            $tags = explode(",", $dish->tags_id);
            $score = 0;

            $tag_score = 0;
            foreach ($tags as $tag) {
                if (isset($ranked_tags[$tag])) {
                    $tag_score += $ranked_tags[$tag];
                }
            }
            $score += ($tag_score / count($tags));

            if (isset($rank_by_usage[$dish->id])) {
                $score += $rank_by_usage[$dish->id];
            }

            $dish->score = $score;
        }

        function sortByScore($a, $b)
        {
            if ($a->score < $b->score) {
                return 1;
            } else if ($a->score == $b->score) {
                return 0;
            } else {
                return -1;
            }
        }

        //sort dishes by score
        usort($available_dishes, "sortByScore");

        //select n first
        $dishes_selected = array_slice($available_dishes, 0, min($amount, count($available_dishes)));

        //fill with data
        $output = [];
        foreach ($dishes_selected as $dish) {
            array_push($output, $this->fillDishWithData($dish));
        }
        //ship
        return $output;
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
}
