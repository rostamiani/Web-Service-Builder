<?php 
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('set_session'))
{
    function set_session($key, $value)
    {
        $ci=& get_instance();

        // Read session prefix
        $prefix = $ci->config->config['session_prefix'];

        // Write session
        $_SESSION[$prefix.$key] = $value;
    }
}

if ( ! function_exists('get_session'))
{
    function get_session($key)
    {
        $ci=& get_instance();

        // Read session prefix
        $prefix = $ci->config->config['session_prefix'];

        // Check session availability
        if (! isset($_SESSION[$prefix.$key]))
            return NULL;

        // Read session
        return $_SESSION[$prefix.$key];
    }
}

if ( ! function_exists('isset_session'))
{
    function isset_session($key)
    {
        $ci=& get_instance();

        // Read session prefix
        $prefix = $ci->config->config['session_prefix'];

        // Read session
        return isset($_SESSION[$prefix.$key]);
    }
}

/**
 * Deletes a column from an array
 */
if ( ! function_exists('array_delete_col'))
{
    function array_delete_col(&$array, $key) {
        return array_walk($array, function (&$v) use ($key) {
            unset($v[$key]);
        });
    }
}

/**
 * Deletes a column from an array
 */
if ( ! function_exists('is_date'))
{
    function is_date($x) {
        return (date('Y-m-d', strtotime($x)) == $x);
    }
}

/**
 * Deletes a column from an array
 */
if ( ! function_exists('is_datetime'))
{
    function is_datetime($x) {
        return (date('Y-m-d H:i:s', strtotime($x)) == $x);
    }
}