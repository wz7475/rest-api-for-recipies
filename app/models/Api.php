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
        foreach ($results as $dish) {
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
        if(count($bucket_disliked)) $worst_tag_occurences = max($bucket_disliked);
        if(count($bucket_used)) $best_tag_occurences = max($bucket_used);
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
        foreach($used_dishes as $dish)
        {
            //PÓŹNIEJ BĘDZIE TO FUNKCJA OD CZASU
            $rank_by_usage[$dish->dish_id] = -1;
        }
        return $rank_by_usage;
    }

    public function getRecommendations($amount, $user_id)
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
            
            if(isset($rank_by_usage[$dish->id]))
            {
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
        return array_slice($available_dishes, 0, min($amount, count($available_dishes)));
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
