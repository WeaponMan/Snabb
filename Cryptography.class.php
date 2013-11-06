<?php

namespace Snabb;

class Cryptography {
  
  public static function &random($lenght, $stack = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ') {
    $random = '';
    for($stack_length = strlen($stack)-1; $lenght > 0; $lenght--)
      $random .= $stack[mt_rand(0, $stack_length)];
    return $random;
  }
  
  public static function bcryptHash($string, $cost = 12) {
    return crypt($string, sprintf('$2y$%02d$%s$', $cost > 31 ? 31 : ($cost < 4 ? 4 : $cost), self::random(22)));
  }
  
  public static function verify($string, $hash) {
    return crypt($string, $hash) === $hash;
  }
}