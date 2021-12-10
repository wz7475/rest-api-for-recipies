<?php
class Api extends Controller {
    public function __construct() {
        $this->postModel = $this->model('Post');
    }

    public function get_dishes() {
        $dishes = $this->postModel->findAlldishes();

        $data = [
            'dishes' => $dishes
        ];

        $this->view('api/dishes', $data);
    }

}

