<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
    Add frequent callbacks here
 */

if(! function_exists('add_company_id_callback'))
{
    /**
     * Adds company_id of the logged in user to the record
     *
     * @param stdClass[] $record
     * @return stdClass[]
     */
    function add_company_id_callback($record)
    {
        $record->company_id = $this->token_data->company_id;
        return $record;
    }
}

