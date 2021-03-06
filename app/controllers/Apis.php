<?php
// session_start();
class Apis extends Controller
{
    public function __construct()
    {
        $this->apiModel = $this->model('Api');
    }

    public function dishes()
    {
        $dishes = $this->apiModel->findAlldishes();
        $data = [
            'json' => $dishes
        ];
        $this->view('api/display_json', $data);
    }

    public function getDish($id)
    {
        $dish = $this->apiModel->findDishByID($id);

        $data = [
            'json' => $dish
        ];

        $this->view('api/display_json', $data);
    }

    public function disheswithtag($tag_id)
    {
        $dishes = $this->apiModel->dishesWithTag($tag_id);
        $data = [
            'json' => $dishes
        ];
        $this->view('api/display_json', $data);
    }

    public function getRecommendations($tag_id)
    {
        $user_id = 1;
        $amount = 1;
        
        $recommedned_dish = $this->apiModel->getRecommendations($amount, $user_id, $tag_id);

        $data = [
            'json' => $recommedned_dish
        ];


        if (!file_exists(APPROOT . '/cache')) {
            mkdir(APPROOT . '/cache', 0777, true);
        }

        File_put_contents(APPROOT . '/cache' . "/recommendation.json", json_encode($data));
        $this->view('api/display_json', $data);
    }

    public function getESPRecipie()
    {
        if (!(file_exists(APPROOT . '/cache' . "/recommendation.json"))) {
            $data["json"] = [
                "recipie" => "Choose recomendation from mobile app first!"
            ];
        } else {
            $data["json"] = [
                "recipie" => file_get_contents(APPROOT . '/cache' . "/recommendation.json")
            ];
        }

        $this->view('api/display_json', $data);
    }

    //All things about "used"
    public function getUsed()
    {
        $user_id = 1;
        $used_ids = $this->apiModel->getUsedDishes($user_id);

        $data = [
            'json' => $used_ids
        ];

        $this->view('api/display_json', $data);
    }

    public function add_dish_object()
    {
        $data = [];
        $data["name"]  = $_POST["name"];
        $data["description"] =  $_POST["description"];
        $data["image"] =  $_POST["image"];
        $data["tags_id"]  = $_POST["tags_id"];
        $data["recipie"]  = $_POST["recipie"];

        $this->apiModel->add_recipie($data);

        $recipie_id = $this->apiModel->findRecipieBySteps($data["recipie"])->id;
        $data["recipie_id"]  = $recipie_id;
        $this->apiModel->addDishObject($data);
    }

    public function tags()
    {
        $recipie = $this->apiModel->findAllTags();

        $data = [
            'json' => $recipie
        ];

        $this->view('api/display_json', $data);
    }

    public function gettag($id)
    {
        $recipie = $this->apiModel->findTagByID($id)->name;

        $data = [
            'json' => $recipie
        ];

        $this->view('api/display_json', $data);
    }

    public function addTag()
    {
        if (!(isset($_POST['tagname']))) {
            return false;
        }
        $name = $_POST['tagname'];
        return $this->apiModel->addTag($name);
    }


    public function addToUsed()
    {
        $user_id = $_POST["user_id"];
        $dish_id = $_POST["dish_id"];

        if(isset($user_id) and isset($dish_id))
        {
            $this->apiModel->addDishToUsed($dish_id, $user_id);
        }
        else
        {
            die("Missing arguments");
        }
    }

    public function removeFromUsed()
    {
        $user_id = $_POST["user_id"];
        $dish_id = $_POST["dish_id"];

        $this->apiModel->removeDishFromUsed($dish_id, $user_id);
    }

    //All things about opinions
    public function setOpinion()
    {
        $tag_id = $_GET["tag_id"];
        // $user_id = $_GET["user_id"];
        $user_id = 1;
        $opinion_coef = $_GET["opinion_coef"];

        $this->apiModel->setOpinion($user_id, $tag_id, $opinion_coef);
    }

    public function getUserOpinions()
    {
        $user_id = 1;
        $opinions = $this->apiModel->getUserOpinions($user_id);
        
        $data = [
            "json" => $opinions
        ];

        $this->view('api/display_json', $data);
    }

    public function getUserTagOpinion()
    {
        $tag_id = $_GET["tag_id"];
        // $user_id = $_GET["user_id"];
        $user_id = 1;

        $opinions = $this->apiModel->getUserTagOpinion($user_id, $tag_id);
        
        $data = [
            "json" => $opinions
        ];

        $this->view('api/display_json', $data);
    }
}