<?php

namespace Mooda\Systems;

class Controllers extends \Mooda {

    protected $config, $model, $service, $languages;

    public function __construct() {
        $this->config = (object) parent::get_config('Application');
        $this->languages = (object) \MoodaVariables::$languages;

        $preloader = parent::preloader_controller();

        if (!empty($preloader['model'])) {
            $this->model = parent::preloader_model($preloader['model']);
        }

        if (!empty($preloader['service'])) {
            $this->service = parent::preloader_service($preloader['service']);
        }
    }

}
