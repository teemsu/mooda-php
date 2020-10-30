<?php

class HelloModel extends \Mooda\Systems\Models {

    public function __construct() {
        parent::__construct();
    }

    public function Print() {
        echo 'Hello World';
    }

}
