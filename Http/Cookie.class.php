<?php

#todo parse raw header in init()
#todo generate raw header in set()

namespace Snabb\Http;

final class Cookie extends \Snabb\StaticClass {
  
  private static $initialized = false, $data = [];
  
  public static function init() {
    if(!self::$initialized && isset($_COOKIE) && is_array($_COOKIE)) {
      self::$data = $_COOKIE;
    }
    self::$initialized = true;
  }
  
  public static function get($name) {
    return isset(self::$data[$name]) ? self::$data[$name] : null;
  }

  public static function set($name, $value, $expire = 0, $path = null, $domain = null, $httponly = true) {
    if(setcookie($name, $value, $expire, $path, $domain, Request::$https, $httponly)) {
      self::$data[$name] = $value;
      return true;
    }
    return false;
  }
  
  public static function delete($name, $path = null, $domain = null) {
    if(setcookie($name, null, time() - 3600, $path, $domain)) {
      unset(self::$data[$name]);
      return true;
    }
    return false;
  }
}

Cookie::init();