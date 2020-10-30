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

        $controller = parent::get_controller($this->route['controller']);

        if (!method_exists(($this->route['controller'] . 'Controller'), $this->route['method'])) {
            return parent::halt('undefined controller method.');
        }

        $method = $this->route['method'];
        $param_array = empty($this->route['param']) ? null : $this->route['param'];

        return $controller->$method($param_array);
    }

}
