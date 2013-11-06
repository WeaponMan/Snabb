<?php

namespace Snabb\Tools;

class Arrays {

    public static function &selective_keys(array $from, $keys) {
        $return = array();
        foreach ((array) $keys as $key)
            $return[$key] = $from[$key];
        return $return;
    }

    public static function translate($key, array $array, $non_exist_replacement = null) {
        return isset($array[$key]) ? $array[$key] : (($non_exist_replacement === null) ? $key : $non_exist_replacement);
    }

    public static function &leave_empty(array $from) {
        foreach ($from as $key => $value)
            if ($value === '' || $value === null)
                unset($from[$key]);
        return $from;
    }

    public static function &replace_empty(array $from, $replacement = null) {
        foreach ($from as $key => $value)
            if ($value === '' || $value === null)
                $from[$key] = $replacement;
        return $from;
    }

    public static function in_all(array $needles, array $haystack, $strict = false) {
        foreach ($needles as $needle)
            if (!in_array($needle, $haystack, $strict))
                return false;
        return true;
    }

    public static function is_assoc(array $array) {
        return (bool) count(array_filter(array_keys($array), 'is_string'));
    }
}