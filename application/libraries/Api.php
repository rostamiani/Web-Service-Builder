<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Api
{
    protected $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
    }

    /**
     * Generate error code as json
     */
    public function generate_output_json(int $code, $info=[], $custom_message = NULL)
    {
        if($this->ci->config->item('debug_display_queries'))
        {
            foreach($this->ci->db->queries as $query)
            {
                echo "$query\n\n";
            }
        }

        // Calculate output
        $output_json = json_encode($this->generate_output($code, $info, $custom_message), JSON_UNESCAPED_UNICODE);

        // If this is production server, just exit sowing message
        if (ENVIRONMENT == 'production')
        {
            // Exit can be much faster than echo as I know that this is the last step
            exit($output_json);
        }
        // If not production, show profiler if needed
        else
        {
            $this->ci->output->_display($output_json);
            exit();
        }
    }

    public function generate_output(int $code, $info = [], $custom_message = NULL)
    {
        // Get code info
        $code_info = $this->ci->config->item('api_codes')[$code];

        // Check if the code is valid
        if ($code_info === NULL)
        {
            log_message('error',"Invalid output code.");
            exit('{"code":-105,"message":"Invalid error code"}');
            $this->generate_output_json(-105);
        }

        // Get message from api codes if there is not any custom message
        if($custom_message === NULL)
        {
            $message = $code_info['message'];
        }
        else
        {
            $message = $custom_message;
        }

        // Prepare output
        $output = (object)$info;
        $output->code = $code;
        $output->type = $code_info['type'];
        $output->class = $this->ci->router->class;
        $output->method = $this->ci->router->method;
        $output->message = $message;

        return $output;
    }

    /**
     * Creates a new CAPTCHA and returns the 'img' html tag
     */
    public function create_captcha()
    {
        $this->ci->load->helper('captcha');
        
        // Read captch settings
        $captcha_settings = $this->ci->config->item('captcha_settings');
        $cap = create_captcha($captcha_settings);

        // Save acptcha to the session
        set_session('captcha',$cap['word']);

        // Return img html tag of the generated captcha image
        return $cap['image'];
    }

    /**
     * Checks if the given captcha is correct
     */
    public function check_captcha($word)
    {
        return $word == get_session('captcha');
    }

    /**
     * Get json from http request
     * defults: defalt values if the values are not exist
     */
    public function get_input($defults = [])
    {
        // Get json data
        $json = file_get_contents('php://input');

        // Check if there is some json input
        if (empty($json) && ENVIRONMENT == 'development')
        {
            // If there is not any json, check post
            if (empty($_POST))
            {
                // If no input
                $input =[];
            }
            else
            {
                // If there is some post, return it as input
                $input = $_POST;
            }
        }
        else
        {
            $input = json_decode($json);

            // Check json convertion
            if(is_null($input) && $json !== '')
            {
                $this->generate_output_json(-1);
            }
        }

        // Set defult values
        foreach ($defults as $key => $value) {
            $input[$key] = isset($input[$key]) ? $input[$key] : $value;
        }

        return $input;
    }

    // Compiles db name if it has any pattern
    public function compile_db_name(string $db_name,stdClass $db_info)
    {
        // Check if database postfixes are available
        $patterns = [
            '{db_postfix}' => $db_info->db_postfix,
            '{db_year}' => $db_info->default_year
        ];

        return str_replace(array_keys($patterns), $patterns, $db_name);
    }

    public function jwt_check($token)
    {
        try
        {
            $token_data = AUTHORIZATION::validateTimestamp($token);
        }
        catch(UnexpectedValueException $e)
        {
            log_message('error','Login failed');
            return false;
        }

        return $token_data;
    }
    
}

/* End of file Api.php */
