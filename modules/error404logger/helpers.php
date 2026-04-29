<?php
if(!function_exists('evo')) {
    function evo() {
        global $modx;
        return $modx;
    }
}

if(!function_exists('db')) {
    function db() {
        global $modx;
        return $modx->db;
    }
}

if(!function_exists('event')) {
    function event() {
        global $modx;
        return $modx->event;
    }
}

if(!function_exists('getv')) {
    function getv($key,$default=null) {
        return $_GET[$key] ?? $default;
    }
}

if(!function_exists('serverv')) {
    function serverv($key,$default=null) {
        return $_SERVER[$key] ?? $default;
    }
}

if(!function_exists('globalv')) {
    function globalv($key,$default=null) {
        return $GLOBALS[$key] ?? $default;
    }
}

if(!function_exists('array_get')) {
    function array_get($array,$key,$default=null) {
        return $array[$key] ?? $default;
    }
}
