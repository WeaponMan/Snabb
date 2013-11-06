<?php

/**
 * @specification of $this->table
 * array(
 * '/' => 'homepage.php',
 * '/articles' => 'articles.php',
 * '/articles/comments' => 'arc_comments.php'
 * )
 * @description Router without implementation of privileged pages
 */

namespace Snabb\Routing;

class SimpleRouter extends Router {

    public function findRoute($privilegesFunc = null) {
        $path = self::getCurrentRoute();
        $filename = '';

        if (isset($this->table[$path]))
            $filename = $this->table[$path];
        else if (isset($this->table[$path . '/']))
            $filename = $this->table[$path . '/'];
        else if (isset($this->table[substr($path, 0, strlen($path) - 1)]) and strrpos($path, '/') ===  (strlen($path) - 1))
            $filename = $this->table[substr($path, 0, strlen($path) - 1)];

        if($filename !== '' and file_exists($this->pagesDir.$filename))
            return $this->pagesDir.$filename;
            
        if ($this->not_found_page_path === null or !file_exists($this->pagesDir . $this->not_found_page_path)) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
            header('Status: 404 Not Found');
            $_SERVER['REDIRECT_STATUS'] = 404;
            exit();
        }

        return $this->pagesDir . $this->not_found_page_path;
    }

}