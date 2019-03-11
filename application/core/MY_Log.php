<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Log extends CI_Log {

	/**
	 * This counter prevents errors that heppens in this function
	 * Othervise an infinite loop will happen
	 */
    public function __construct()
    {
        parent::__construct();
    }

    public function write_log($level, $msg)
	{
		parent::write_log($level, $msg);

		if ($this->_enabled === FALSE)
		{
			return FALSE;
		}

		$level = strtoupper($level);

		if (( ! isset($this->_levels[$level]) OR ($this->_levels[$level] > $this->_threshold))
			&& ! isset($this->_threshold_array[$this->_levels[$level]]))
		{
			return FALSE;
		}

		// Disable error reporting for this class preventing infinite loops
		$this->_enabled = FALSE;
		
		// Insert message into database
		$this->ci =& get_instance();

		if(empty($this->ci))
		{
			return;
		}
		
		$this->ci->load->model('error_log_model');
		$this->ci->error_log_model->insert($level, $msg);
		
		$this->_enabled = TRUE;
	}
}

/* End of file MY_Log.php */
