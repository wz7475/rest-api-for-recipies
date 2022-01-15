<?php
    class Brain {
        public function __construct($db) {
            $this->db = $db;
        }

        public function getRankedTags($user_id)
        {
            /*
            IF a tag is in disliked dishes -> negative score
            IF a tag is in used dishes -> positive score
            */
    
            //Get all disliked tags
            // $this->db->query('
            // SELECT tags_id FROM dishes
            // WHERE id in (
            //     SELECT dish_id FROM dislikes
            //     WHERE user_id = :user_id
            //     )
            // ');
            // $this->db->bind(':user_id', $user_id);
            // $disliked_tags = $this->db->resultSet();
    
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
            // $bucket_disliked = [];
            // foreach ($disliked_tags as $tags) {
            //     $tags = explode(",", $tags->tags_id);
            //     foreach ($tags as $tag) {
            //         if (isset($bucket_disliked[$tag])) {
            //             $bucket_disliked[$tag]++;
            //         } else {
            //             $bucket_disliked[$tag] = 1;
            //         }
            //     }
            // }
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
            // if (count($bucket_disliked)) $worst_tag_occurences = max($bucket_disliked);
            if (count($bucket_used)) $best_tag_occurences = max($bucket_used);
            $ranked_tags = [];
    
            // foreach (array_keys($bucket_disliked) as $tag) {
            //     $ranked_tags[$tag] = - ($bucket_disliked[$tag] / $worst_tag_occurences);
            // }
    
            foreach (array_keys($bucket_used) as $tag) {
                if (isset($ranked_tags[$tag])) {
                } else {
                    $ranked_tags[$tag] = ($bucket_used[$tag] / $best_tag_occurences);
                }
            }
            return $ranked_tags;
        }
    
        public function getTemporalRank($user_id)
        {
            $this->db->query('
            SELECT dish_id, time_stamp FROM used 
            WHERE user_id = :user_id
            ');
            $this->db->bind(':user_id', $user_id);
            $used_dishes = $this->db->resultSet();
            $BASE_TIME_UNIT = (3600*24*7);
            $SQUISH = 4;
            $OFFSET = 2;

            $rank_by_usage = [];
            foreach ($used_dishes as $dish) {
                $dish_id = $dish->dish_id;
                $time_stamp = $dish->time_stamp;
                $delta_t = time()-$time_stamp;
                $rank_by_usage[$dish_id] = tanh($delta_t*$SQUISH/$BASE_TIME_UNIT-$OFFSET)/2+0.5;
            }
            return $rank_by_usage;
        }

        public function getRecommendedRaw($amount, $user_id, $special_tag)
        {
            //get all recipies that have the specified tag (are in the specified category)
            $this->db->query('
            SELECT * FROM dishes
            WHERE tags_id like ":the_tag,%" or tags_id like "%,:the_tag,%" or tags_id like "%,:the_tag"
            ');
            $this->db->bind(':user_id', $user_id);
            $this->db->bind(':the_tag', $special_tag);
            $available_dishes = $this->db->resultSet();

            $rank_by_usage = $this->getTemporalRank($user_id);
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

            return $dishes_selected;
        }
    }
?>