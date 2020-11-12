<?php

class Mooda {

    protected $database;

    protected function halt($title, $message = null) {
        echo '<h1>' . $title . '</h1>';
        echo '<div>' . $message . '</div>';
        exit(0);
    }

    protected function route_detect($str) {
        $return = array('controller' => null, 'method' => null, 'param' => null);

        if (empty($str)) {
            return $return;
        }

        $route = trim($str, '/');
        $route_sep = explode('/', $route);

        $return['controller'] = empty($route_sep['0']) ? null : $route_sep['0'];
        $return['method'] = empty($route_sep['1']) ? null : $route_sep['1'];

        if (count($route_sep) > 2) {
            $return['param'] = array_slice($route_sep, 0, 2);
        }

        return $return;
    }

    protected function get_config($filename) {
        if (empty($filename)) {
            return null;
        }

        if (isset(MoodaVariables::$config[$filename])) {
            return MoodaVariables::$config[$filename];
        }

        $filepath = sprintf('%s%sConfigures/%s.php', __DIR__, DIRECTORY_SEPARATOR, $filename);

        if (!file_exists($filepath)) {
            return null;
        }

        MoodaVariables::$config[$filename] = include($filepath);

        return MoodaVariables::$config[$filename];
    }

    protected function preloader_controller($route_uri = null) {
        if (empty($route_uri)) {
            $route_uri = ROUTE_URI;
        }

        $route = $this->route_detect($route_uri);

        if (empty($route['controller'])) {
            return null;
        }

        $cnf = (array) $this->get_config('Controller');

        if (empty($cnf)) {
            return null;
        }

        $controller = $route['controller'];
        $cnf_data = empty($cnf[$controller]) ? array() : (array) $cnf[$controller];

        if (!empty($route['method'])) {
            $controller2 = $controller . '/' . $route['method'];
            $cnf_data2 = empty($cnf[$controller2]) ? array() : (array) $cnf[$controller2];

            if (!empty($cnf_data2) && is_array($cnf_data2)) {
                $cnf_data = array_merge($cnf_data, $cnf_data2);
            }
        }

        return $cnf_data;
    }

    protected function preloader_model($models) {
        if (!is_array($models)) {
            return null;
        }

        $model_obj = new \stdClass;

        foreach ($models as $model) {
            if (empty($model)) {
                continue;
            }

            $model_obj->$model = $this->get_model($model);
        }

        return $model_obj;
    }

    protected function preloader_service($services) {
        if (!is_array($services)) {
            return null;
        }

        $service_obj = new \stdClass;

        foreach ($services as $service) {
            if (empty($service)) {
                continue;
            }

            $service_obj->$service = $this->get_service($service);
        }

        return $service_obj;
    }

    protected function get_controller($controller) {
        if (empty($controller)) {
            return $this->halt('undefined controller');
        }

        $class_name = sprintf('%sController', $controller);
        $class_file = (CONTROLLERS_DIRECTORY . $class_name . '.php');

        return $this->get_class($class_name, $class_file);
    }

    protected function get_model($model) {
        if (empty($model)) {
            return $this->halt('undefined model');
        }

        $class_name = sprintf('%sModel', $model);
        $class_file = (MODELS_DIRECTORY . $class_name . '.php');

        return $this->get_class($class_name, $class_file);
    }

    protected function get_service($service) {
        if (empty($service)) {
            return $this->halt('undefined service');
        }

        $class_name = sprintf('%sService', $service);
        $class_file = (SERVICES_DIRECTORY . $class_name . '.php');

        return $this->get_class($class_name, $class_file);
    }

    protected function get_class($class_name, $class_file) {
        if (class_exists($class_name)) {
            goto NEW_CLASS;
        }

        if (!file_exists($class_file)) {
            return $this->halt('(error) "' . $class_name . '"', 'File not found.');
        }

        include $class_file;

        if (!class_exists($class_name)) {
            return $this->halt('(error) "' . $class_name . '"', 'Undefined Class as a Function.');
        }

        NEW_CLASS:

        try {
            $class_fn = new $class_name();
        } catch (Exception $ex) {
            return $this->halt('(error) "' . $class_name . '"', $ex->getMessage());
        }

        return $class_fn;
    }

}

class MoodaVariables {
    public static $config = array();
    public static $database = array();
    public static $languages = array();

    public static function init(){
        self::$config = array();
        self::$database = array();
        self::$languages = array();
    }
}

MoodaVariables::init();
