<?php

namespace Mooda\Systems;

class Models extends \Mooda {

    protected $config, $service, $database, $languages;

    public function __construct() {
        $this->config = (object) parent::get_config('Application');
        $this->languages = (object) \MoodaVariables::$languages;

        $preloader = parent::preloader_controller();

        if (!empty($preloader['database'])) {
            $this->database = $this->preloader_database($preloader['database']);
        }

        if (!empty($preloader['service'])) {
            $this->service = parent::preloader_service($preloader['service']);
        }
    }

    private function preloader_database($databases) {
        if (!is_array($databases)) {
            return null;
        }

        $database_cnf = (array) parent::get_config('Database');

        if (empty($database_cnf)) {
            return null;
        }

        $database_obj = new \stdClass;

        foreach ($databases as $database) {
            if (empty($database)) {
                continue;
            }

            if (empty($database_cnf[$database])) {
                continue;
            }

            $database_cnf[$database]['database'] = $database;

            try {
                $database_obj->$database = new \Mooda\Database\PDO_MYSQL($database_cnf[$database]);
                $database_obj->$database->Connect();
            } catch (Exception $ex) {
                parent::halt('(error) Database', $ex->getMessage());
                break;
            }
        }

        return $database_obj;
    }

}
