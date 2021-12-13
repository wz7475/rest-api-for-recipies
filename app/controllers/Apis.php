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

    public function getRecommendations()
    {
        $user_id = $_GET["user_id"];
        $amount = $_GET["amount"];

        $dishes = $this->apiModel->getRecommendations($amount, $user_id);

        $data = [
            'json' => $dishes
        ];

        $this->view('api/display_json', $data);
    }

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
}
