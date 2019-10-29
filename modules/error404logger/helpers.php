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

if(!function_exists('input_get')) {
    function input_get($key,$default=null) {
        if(!isset($_GET[$key])) {
            return $default;
        }
        return $_GET[$key];
    }
}

if(!function_exists('server_var')) {
    function server_var($key,$default=null) {
        if(!isset($_SERVER[$key])) {
            return $default;
        }
        return $_SERVER[$key];
    }
}
