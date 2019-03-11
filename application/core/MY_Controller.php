<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller {

    public $__model_name = '"$__model_name" variable is not defined in the controller';

    protected $__add_validation = [];
    protected $__update_validation = [];
    protected $__delete_validation = [
        'ids' => []
    ];
    protected $__list_validation = [
        'start' => ['is_numeric'],
        'length' => ['is_numeric'],
        'keyword' => [],
        'sort' => []
    ];

    protected $__list_fields = '';

    protected $__skip_auth_methods = [];
    protected $__skip_validation = FALSE;

    /**
     * Valid events:
     * 
     * before_list, after_list
     * before_add, after_add
     * before_update, after_update
     * before_delete, after_delete
    */
    protected $events = [];

    public $token_data;

    public function __construct()
    {
        parent::__construct();

        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: content-type,token');
        header('Access-Control-Allow-Methods:POST,GET');
        header('Content-Type: application/json');

        // Display errors and handle them as json output
        ini_set('display_errors', 1);

        // Check if the user is authenticated
        $token = $this->input->get_request_header('token');

        // =-=-=-=-=-=-=-=-=-=- For testing =-=-=-=-=-=-=-=-=-=-
        if (ENVIRONMENT != 'production' && empty($token))
        {
            $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoiNiIsImRiX3Bvc3RmaXgiOiJucCIsImRlZmF1bHRfeWVhciI6IjEzOTciLCJjb21wYW55X2lkIjoiMiIsImNoYXJ0X2lkIjoiMyIsInRpbWVzdGFtcCI6MTU1MDg5NDM2M30.ki_X0mBlZRHSoAMcVSwZkxaF4ngY7Ia9x5oWYSrpxTA";

        }

        if(ENVIRONMENT != 'production' && $this->config->config['debug_enable_profiler'])
        {
            $this->output->enable_profiler(TRUE);
        }

        // =-=-=-=-=-=-=-=-=-=- For testing =-=-=-=-=-=-=-=-=-=-

        // If checking user is needed
        $method = $this->router->fetch_method();
        if (! in_array($method, $this->__skip_auth_methods)) {
            // If the user is not logged in and the current page is not login page, show error
            // if(uri_string() != "user/auth" && uri_string() != "api/codes" && ! $this->token_data = $this->api->jwt_check($token))
            if(! $this->token_data = $this->api->jwt_check($token))
            {
                $this->api->generate_output_json(-114);
            }
        }

        // =-=-=-=-=-=-=-=-=-=- For testing =-=-=-=-=-=-=-=-=-=-
        if(ENVIRONMENT != 'production' && $this->config->config['debug_enable_profiler'])
        {
            // If there is a chart_id on header, assign it to the token data
            $header_chart_id = $this->input->get_request_header('chart_id');
            if (! empty($header_chart_id))
            {
                $this->token_data->chart_id = $header_chart_id;
            }

            // If there is a user_id on header, assign it to the token data
            $header_user_id = $this->input->get_request_header('user_id');
            if (! empty($header_user_id))
            {
                $this->token_data->user_id = $header_user_id;
            }
        }
        // =-=-=-=-=-=-=-=-=-=- For testing =-=-=-=-=-=-=-=-=-=-

        // Auto Load models
        $this->load->model($this->__model_name);
        $this->load->model('setting_model');
    }

    /**
     * Gets a single record and adds it to the database
     *
     * @return void
     */
    public function add()
    {
        // Get input messages
        $input = $this->api->get_input();

        // Validate
        if(! $this->__skip_validation)
        {
            $this->validation->run($input, $this->__add_validation);
        }

        // All fields cannot be empty
        if (empty($input))
        {
            $this->api->generate_output_json(-115);
        }
        
        $input = $this->trigger('before_add', $input);

        // insert the record into the table
        $id = $this->{$this->__model_name}->insert($input);

        // Add id to the input and send it to the after_add event
        $input->id = $id;
        $this->trigger('after_add', $input);

        // Return success
        $this->api->generate_output_json(1, ['data'=>$id]);
    }

    /**
     * gets an array or records and adds them one by one in a loop
     *
     * @return void
     */
    public function add_array()
    {
        // Get input messages
        $input = $this->api->get_input();

        // Show error if input is not an array
        if(! is_array($input))
        {
            $this->api->generate_output_json(-128, NULL, 'Input is not an array');
        }

        // Validate
        if(! $this->__skip_validation)
        {
            foreach ($input as $item) {
                $this->validation->run($item, $this->__add_validation);
            }
        }

        // Insert records one by one
        foreach ($input as $record) {
            
            $record = $this->trigger('before_add', $record);
            
            // insert the record into the table
            $id = $this->{$this->__model_name}->insert($record);
            
            // Add id to the input and send it to the after_add event
            $record->id = $id;
            $this->trigger('after_add', $record);
        }

        // Return success
        $this->api->generate_output_json(1, ['data'=>$id]);
    }

    /**
     * gets an array or records and adds them as a batch using one single insert command
     *
     * @return void
     */
    public function add_batch()
    {
        // Get input messages
        $input = $this->api->get_input();

        // Show error if input is not an array
        if(! is_array($input))
        {
            $this->api->generate_output_json(-128, NULL, 'Input is not an array');
        }

        // Validate
        if(! $this->__skip_validation)
        {
            foreach ($input as $item) {
                $this->validation->run($item, $this->__add_validation);
            }
        }

        $input = $this->trigger('before_add_batch', $input);

        // insert all the records into the table
        $success = $this->{$this->__model_name}->insert_batch($input);

        // Add id to the input and send it to the after_add event
        $this->trigger('after_add_batch', $input);

        // Return success
        $this->api->generate_output_json(1);
    }

    public function list()
    {
        $input = $this->api->get_input();

        // Validation
        if(! $this->__skip_validation)
        {
            $this->validation->run($input, $this->__list_validation);
        }

        // Set search parameters
        if(! empty($input->keyword))
        {
            $this->{$this->__model_name}->keyword($input->keyword);
        }
        if(isset($input->start) || isset($input->length))
        {
            $this->{$this->__model_name}->limit(
                $input->length ?? $this->config->item('default_query_length'),
                $input->start ?? 0
            );
        }
        if(isset($input->sort))
        {
            $this->{$this->__model_name}->sort($input->sort);
        }

        $input = $this->trigger('before_list', $input);

        // Get data from database
        $result = $this
            ->{$this->__model_name}
            ->add_fields($this->__list_fields)
            ->list();

        $result->data = $this->trigger('after_list', $result->data);

        // Return output
        $this->api->generate_output_json(1, $result);
    }

    /**
     * Get a single record with the given id
     */
    public function single(int $id = NULL)
    {
        $id = $this->trigger('before_single', ['id'=>$id])['id'];

        $record = $this
            ->{$this->__model_name}
            ->add_fields($this->__list_fields)
            ->get($id);

        // If the item did not found, return error
        if($record === NULL)
        {
            $this->api->generate_output_json(-109);
        }

        $record = $this->trigger('after_single', $record);

        // If the record found, return success
        if(! empty($record))
        {
            $this->api->generate_output_json(1, ['data'=>$record]);
        }
        else
        {
            $this->api->generate_output_json(-109);
        }
    }

    public function update(int $id)
    {
        $input = $this->api->get_input();

        // Validate
        if(! $this->__skip_validation)
        {
            $this->validation->run($input, $this->__update_validation, $id);
        }

        // All fields cannot be empty
        if (empty($input))
        {
            $this->api->generate_output_json(-115);
        }

        // Check if the selected id is available
        if(! $this->{$this->__model_name}->has($id))
        {
            $this->api->generate_output_json(-109);
        }

        // Include id in input
        $input->id = $id;

        // Execute the callback
        $input = $this->trigger('before_update', $input);

        // Apply update
        $this->{$this->__model_name}->update($input, $input->id);

        $this->trigger('after_update', $input);

        // Return success
        $this->api->generate_output_json(1);
    }

    public function delete($id = NULL)
    {
        $input = $this->api->get_input();

        // Validate
        if(! $this->__skip_validation)
        {
            $this->validation->run($input, $this->__delete_validation);
        }

        // If there is an id in the url, convert it to array
        if (! is_null($id))
        {
            $ids = [$id];
        }
        // Otherwise get the list of ids from input
        else if(isset($input->ids))
        {
            $ids = $input->ids;
        }
        // If the ids are not set, return error
        else
        {
            $this->api->generate_output_json(-116);
        }

        // If these ids are not available in the table, return error
        if(! $this->{$this->__model_name}->has($ids))
        {
            $this->api->generate_output_json(-109,['data'=>$ids]);
        }

        // Execute the callback
        $ids = $this->trigger('before_delete', $ids);

        // Do delete
        $affected = $this->{$this->__model_name}->delete_many($ids);

        $this->trigger('after_delete', $ids);

        // Return success
        $this->api->generate_output_json(1, ['data'=>"Affected rows: $affected"]);
        return;
    }

    private function trigger($event, $data = NULL, $last = FALSE)
    {
        // Get events
        if(! isset($this->events[$event]))
        {
            return $data;
        }
        $events = $this->events[$event];

        // If the event is just a single event as string, make it an array
        if(! is_array($events))
        {
            $events = [$events];
        }

        // execute all methods for this event
        foreach ($events as $method)
        {
            // Check if the callback exists
            if(method_exists($this, $method))
            {
                // If the function returned nothing, do not change the input data
                $data = call_user_func_array([$this, $method], [$data, $last]) ?? $data;
            }
            elseif(function_exists($method))
            {
                // If the function returned nothing, do not change the input data
                $data = call_user_func_array($method, [$data, $last]) ?? $data;
            }
            else
            {
                // Show error if the function does not exist
                $this->api->generate_output_json(-110,['data'=>$method]);
            }
            
        }

        return $data;
    }

    protected function log($type, $title, $message = NULL, $target_id = NULL, $target_table = NULL)
    {
        $this->load->model('log_model');

        $fields = (object)[
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'target_table' => $target_table ?? $this->api->compile_db_name($this->{$this->__model_name}->__table_name, $this->token_data),
            'target_id' => $target_id,
            'user_id' => $this->token_data->user_id
        ];

        return $this->log_model->insert($fields);
    }

    protected function log_info($title, $message = NULL, $target_id = NULL, $target_table = NULL)
    {
        $this->log(DB_LOG_TYPE_INFO, $title, $message, $target_id, $target_table);
    }
    protected function log_success($title, $message = NULL, $target_id = NULL, $target_table = NULL)
    {
        $this->log(DB_LOG_TYPE_SUCCESS, $title, $message, $target_id, $target_table);
    }
    protected function log_warning($title, $message = NULL, $target_id = NULL, $target_table = NULL)
    {
        $this->log(DB_LOG_TYPE_WARNING, $title, $message, $target_id, $target_table);
    }
    protected function log_danger($title, $message = NULL, $target_id = NULL, $target_table = NULL)
    {
        $this->log(DB_LOG_TYPE_DANGER, $title, $message, $target_id, $target_table);
    }

    private function log_if_needed()
    {
        switch ($variable) {
            case 'value':
            
            default:
        }
    }

    /**
        Special events
     */

    
}

/* End of file MY_Controller.php */
