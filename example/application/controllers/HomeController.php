<?php

class HomeController extends \Mooda\Systems\Controllers {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $this->model->Hello->Print();
    }

}
