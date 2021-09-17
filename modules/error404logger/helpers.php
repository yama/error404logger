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
        if(!isset($_GET[$key])) {
            return $default;
        }
        return $_GET[$key];
    }
}

if(!function_exists('serverv')) {
    function serverv($key,$default=null) {
        if(!isset($_SERVER[$key])) {
            return $default;
        }
        return $_SERVER[$key];
    }
}

if(!function_exists('globalv')) {
    function globalv($key,$default=null) {
        if(!isset($GLOBALS[$key])) {
            return $default;
        }
        return $GLOBALS[$key];
    }
}

if(!function_exists('array_get')) {
    function array_get($array,$key,$default=null) {
        if(!isset($array[$key])) {
            return $default;
        }
        return $array[$key];
    }
}
