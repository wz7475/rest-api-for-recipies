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
        $dish = $this->apiModel->findDishById($id);

        $data = [
            'json' => $dish
        ];

        $this->view('api/display_json', $data);
    }

    public function getRecommendations($amount)
    {
        $user_id = $_GET["user_id"];
        $dishes = $this->apiModel->getRecommendations($amount, $user_id);

        $data = [
            'dishes' => $dishes
        ];

        $this->view('api/display_json', $data);
    }
}
