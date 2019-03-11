<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Exceptions extends CI_Exceptions {
    
    public function __construct()
    {
        parent::__construct();

        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: content-type,token');
        header('Access-Control-Allow-Methods:POST,GET');
        header('Content-Type: application/json');
    }
  
    public function show_404($page = '', $log_error = TRUE)
    {
        // If environment is not production, show error
        if(ENVIRONMENT != 'production')
        {
            return parent::show_404($page, $log_error);
        }

        // Show error to the client as json
        $this->generate_output_json(-3,'Error 404: The service does not exist');
    }

	public function show_error($heading, $message, $template = 'error_general', $status_code = 500)
    {
        // If environment is not production, show error
        if(ENVIRONMENT != 'production')
        {
            return parent::show_error($heading, $message, $template , $status_code);
        }

        // Show error to the client as json
        $this->generate_output_json(-3,$heading);
    }

	public function show_exception($exception)
    {
        // If environment is not production, show error
        if(ENVIRONMENT != 'production')
        {
            return parent::show_exception($exception);
        }

        // Show error to the client as json
        $this->generate_output_json(-3,"Exception: ".$exception->getMessage());
    }

	public function show_php_error($severity, $message, $filepath, $line)
    {
        // If environment is not production, show error
        if(ENVIRONMENT != 'production')
        {
            return parent::show_php_error($severity, $message, $filepath, $line);
        }

        // Show error to the client as json
        $this->generate_output_json(-3,"PHP Error");
    }

    private function generate_output_json($code, $info = NULL)
    {
        $output = new stdClass();
        $output->code = '-3';
        $output->message = 'An exception occured. Please see the logs for more information';
        $output = (object) array_merge((array) $output, ['data' => $info]);

        echo json_encode($output, JSON_UNESCAPED_UNICODE);
        exit();
    }
}

/* End of file MY_Exceptions.php */
