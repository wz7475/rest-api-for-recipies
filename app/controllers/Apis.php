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
            'dishes' => $dishes
        ];
        $this->view('api/dishes', $data);
    }

    public function getDish($id)
    {
        $dish = $this->apiModel->findDishById($id);


        $data = [
            'dish' => $dish
        ];

        $this->view('api/getdish', $data);
    }
}
