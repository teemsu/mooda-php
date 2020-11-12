<?php

namespace Mooda\Systems;

class Router extends \Mooda {

    private $route;

    public function Go($route_uri) {
        $this->route = parent::route_detect($route_uri);

        if (empty($this->route['controller'])) {
            return parent::halt('undefined controller');
        }

        if (empty($this->route['method'])) {
            return parent::halt('undefined method');
        }

        $cnf = (array) $this->get_config('Application');

        $this->preloader_language($cnf);

        $controller = parent::get_controller($this->route['controller']);

        if (!method_exists(($this->route['controller'] . 'Controller'), $this->route['method'])) {
            return parent::halt('undefined controller method.');
        }

        $method = $this->route['method'];
        $param_array = empty($this->route['param']) ? null : $this->route['param'];

        return $controller->$method($param_array);
    }

    private function select_language($default_lang) {
        $lang_headers = empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? null : explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $lang_header = empty($lang_headers[0]) ? $default_lang : $lang_headers[0];
        $lang_session = empty($_SESSION['accept_language']) ? $lang_header : $_SESSION['accept_language'];
        return (empty($_GET['accept_language']) ? $lang_session : $_GET['accept_language']);
    }

    private function preloader_language($cnf) {
        if (empty($cnf['available_language'])) {
            return null;
        }

        if (!defined('LANGUAGES_DIRECTORY')) {
            return null;
        }

        if (empty(LANGUAGES_DIRECTORY)) {
            return null;
        }

        if (is_string($cnf['available_language'])) {
            $cnf['available_language'] = array($cnf['available_language']);
        }

        $default_lang = isset($cnf['default_language']) ? $cnf['default_language'] : null;
        $select_lang = $this->select_language($default_lang);

        if (!in_array($select_lang, $cnf['available_language'])) {
            return null;
        }

        $dir = LANGUAGES_DIRECTORY . $select_lang . DIRECTORY_SEPARATOR;

        if (!file_exists($dir)) {
            return null;
        }

        $files = array_diff(scandir($dir), array('..', '.'));
        $data = array();

        foreach ($files as $file) {
            $finfo = pathinfo(strtolower($file));

            if (empty($finfo['extension'])) {
                continue;
            }

            if ($finfo['extension'] !== 'php') {
                continue;
            }

            $cnf = include($dir . $file);

            if (!is_array($cnf)) {
                continue;
            }

            ksort($cnf);

            $data[strval($finfo['filename'])] = $cnf;
        }

        ksort($data);

        \MoodaVariables::$languages = $data;
    }

}
