<?php

namespace Snabb\Routing;

class PrivilegedRouter extends Router {

    public function findRoute($privilegesFunc = null) {
        $filename = '';
        $access = false;
        $found = false;
        $path = self::getCurrentRoute();
        foreach ($this->table as $route) {
            if ($path . '/' === $route[0] or $path === $route[0] . '/' or $path === $route[0]) {
                $found = file_exists($this->pagesDir . $route[1]);
                $access = $privilegesFunc($route[2]);
                $filename = $this->pagesDir . $route[1];
                if ($found and $access)
                    break;
            }
        }

        if ($found === false) {
            if ($this->not_found_page_path === null or !file_exists($this->pagesDir . $this->not_found_page_path)) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
                header('Status: 404 Not Found');
                $_SERVER['REDIRECT_STATUS'] = 404;
                exit();
            }
            return $this->pagesDir . $this->not_found_page_path;
        }

        if ($access === false) {
            if ($this->access_denied_page_path === null or !file_exists($this->pagesDir . $this->access_denied_page_path)) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
                header('Status: 403 Forbidden');
                $_SERVER['REDIRECT_STATUS'] = 403;
                exit;
            }
            return $this->pagesDir . $this->access_denied_page_path;
        }
        return $filename;
    }

}