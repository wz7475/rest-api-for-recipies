<?php
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

    public function getRecommendations()
    {

        // $dishes = $this->apiModel->getRecommendations($amount, $user_id);
        /* for recommendation alogoritm development 
        alway return fixed recipe */
        $dish = $this->getDish(1);

        $data = [
            'json' => $dish
        ];

        $this->view('api/display_json', $data);
    }

    //Access to the Dish'es recipie the user wants to display
    public function getESPRecipie($user_id)
    {
        $recipie = $this->apiModel->getESPRecipie($user_id);

        $data = [
            'json' => $recipie
        ];

        $this->view('api/display_json', $data);
    }

    
    public function setESPRecipie()
    {
        $dish_id = $_GET["dish_id"];
        $user_id = $_GET["user_id"];

        $this->apiModel->setESPRecipie($user_id, $dish_id);
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
        if (!(isset($_POST['tagname']))){
            return false;
        }
        $name = $_POST['tagname'];
        return $this->apiModel->addTag($name);
    }

    //All things about "used"
    public function getUsed($user_id)
    {
        $used_ids = $this->apiModel->getUsedDishes($user_id);

        $data = [
            'json' => $used_ids
        ];

        $this->view('api/display_json', $data);
    }

    public function addToUsed()
    {
        $user_id = $_POST["user_id"];
        $dish_id = $_POST["dish_id"];

        $this->apiModel->addDishToUsed($dish_id, $user_id);
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
        $user_id = $_GET["user_id"];
        $opinion_coef = $_GET["opinion_coef"];

        $this->apiModel->setOpinion($user_id, $tag_id, $opinion_coef);
    }

    public function getUserOpinions($user_id)
    {
        $opinions = $this->apiModel->getUserOpinions($user_id);
        
        $data = [
            "json" => $opinions
        ];

        $this->view('api/display_json', $data);
    }

    public function getUserTagOpinion()
    {
        $tag_id = $_GET["tag_id"];
        $user_id = $_GET["user_id"];

        $opinions = $this->apiModel->getUserTagOpinion($user_id, $tag_id);
        
        $data = [
            "json" => $opinions
        ];

        $this->view('api/display_json', $data);
    }
}