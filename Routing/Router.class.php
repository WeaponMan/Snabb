<?php
namespace Snabb\Routing;


abstract class Router extends \Snabb\Object {

    protected $table, $pagesDir, $not_found_page_path, $access_denied_page_path;
    public static $route, $path, $path_route, $path_route_args;
    
    public function __construct(array $table, $pagesDir, $not_found_page_path = null, $access_denied_page_path = null) {
        $this->table = $table;
        $this->pagesDir = strrpos($pagesDir, '/') === (strlen($pagesDir) - 1) ? $pagesDir : $pagesDir . '/';
        $this->not_found_page_path = $not_found_page_path;
        $this->access_denied_page_path = $access_denied_page_path;
    }

    abstract public function findRoute($privilegesFunc = null);

    public function setNotFoundPage($filepath) {
        $this->not_found_page_path = $filepath;
    }

    public function setAccessDeniedPage($filepath) {
        $this->access_denied_page_path = $filepath;
    }
}

Router::$path =  str_replace('index.php', '', $_SERVER['PHP_SELF']);
Router::$path_route = str_replace('?' . $_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']);
Router::$route = str_replace(Router::$path, '/', str_replace('?' . $_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']));
Router::$path_route_args =  htmlentities($_SERVER['REQUEST_URI']);

