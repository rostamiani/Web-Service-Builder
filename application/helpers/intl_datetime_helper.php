<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include __DIR__.'/../third_party/IntlDateTime.php';

if (! function_exists('newIntlDateTime')) {

    /**
	 * Creates a new instance of IntlDateTime
     * 
     * Use these rules in format() function:
     * http://userguide.icu-project.org/formatparse/datetime
     * 
	 * @param mixed $time Unix timestamp or strtotime() compatible string or another DateTime object
	 * @param mixed $timezone DateTimeZone object or timezone identifier as full name (e.g. Asia/Tehran) or abbreviation (e.g. IRDT).
	 * @param string $calendar any calendar supported by ICU (e.g. gregorian, persian, islamic, ...)
	 * @param string $locale any locale supported by ICU
	 * @param string $pattern the date pattern in which $time is formatted.
     * @return \farhadi\IntlDateTime
     */
    function newIntlDateTime($time = null, $timezone = null, $calendar = 'gregorian', $locale = 'en_US', $pattern = null)
    {
        return new \farhadi\IntlDateTime($time ,$timezone , $calendar, $locale, $pattern);
    }

    

}